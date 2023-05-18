<?php

namespace Boiler\Core\Engine\Router;


use Exception;
use App\Config\RoutesConfig;
use Boiler\Core\Actions\Urls\BaseController;
use Boiler\Core\Database\Schema;
use Boiler\Core\Hashing\Hash;
use Boiler\Core\Admin\Auth;
use Carbon\Carbon;

class Route extends RoutesConfig
{

    static private $routes = array(
        "get"   =>  array(),
        "post"  =>  array(),
        "put" => array(),
        "delete" => array(),
        "patch" => array()
    );


    static private $registered_routes = array();


    static private $domains = array();


    static public $subdomain;


    static public $active_domain;


    static private $route_lookup_list = [];


    static private $controller_namespace = 'App\Controllers\\';


    static private $group_path = "";


    static private $middlewares = [];


    public $names_ = [];


    public $map;



    public function __construct()
    {
    }


    static public function configure()
    {
        static::$domains[static::$domain] = static::$routes;
    }

    static public function subdomain($domains, $callback)
    {
        if (!static::$enable_subdomains) {
            // throw unenabled subdomain actions exceptions
            return false;
        }

        if (!is_array($domains)) {
            $domains = [$domains];
        }

        foreach ($domains as $domain) {
            static::$active_domain = $domain;
            static::$subdomain = static::$active_domain . "." . static::$domain;
            if (!isset(static::$domains[static::$subdomain])) {
                static::$domains[static::$subdomain] =  static::$routes;
            }
            $callback();
            static::$subdomain = null;
        }
    }

    public function as($name)
    {
        $this->set_route_name($name);
    }

    static public function middleware($middleware, $callback)
    {
        static::protected($middleware, $callback);
    }

    static public function protected($middleware, $callback)
    {
        $middlewares = $middleware;

        if (!is_array($middleware) && strpos($middleware, '|')) {
            $middlewares = explode('|', $middleware);
        } else if (is_string($middleware)) {
            $middlewares = [$middleware];
        }

        array_merge(static::$middlewares, $middlewares);
        $callback();
        static::$middlewares = [];
    }

    static public function group($name, $callback)
    {
        $name = !empty($name) ? trim($name, "/") : $name;

        static::$group_path = "/" . $name;
        $callback();
        static::$group_path = "";
    }


    static public function all($path, $controller)
    {
        static::post($path, $controller);
        static::delete($path, $controller);
        static::put($path, $controller);
        static::patch($path, $controller);
        return static::get($path, $controller);
    }

    static public function get($path, $controller)
    {
        $map = static::create_map($path, "get", $controller);
        static::mapRoute($map);

        return static::route_modifier($map);
    }

    static public function post($path, $controller)
    {
        $map = static::create_map($path, "post", $controller);
        static::mapRoute($map);
    }

    static public function put($path, $controller)
    {
        $map = static::create_map($path, "put", $controller);
        static::mapRoute($map);

        return static::route_modifier($map);
    }

    static public function delete($path, $controller)
    {
        $map = static::create_map($path, "delete", $controller);
        static::mapRoute($map);

        return static::route_modifier($map);
    }

    static public function patch($path, $controller)
    {
        $map = static::create_map($path, "patch", $controller);
        static::mapRoute($map);

        return static::route_modifier($map);
    }


    static protected function create_map($path, $method, $controller)
    {
        $path = !empty($path) ? "/" . trim($path, "/") : $path;

        # check group path
        if (static::$group_path != "") {
            $path = static::$group_path . $path;
        }

        return array("url" => $path, "method" => $method, "action" => $controller);
    }

    static protected function mapRoute(array $route)
    {
        $method = strtolower($route["method"]);

        # get url
        $url = "/index" . $route["url"];

        # prepare properties
        $properties = array("action" => $route["action"]);

        $key = Route::interogate($url);

        # if interogate returned an array
        if (is_array($key)) {
            list($key, $params) = $key;

            # add the param key to the properties
            # validate param by
            # @cheking duplicate key
            $properties["params"] = $params;
        }

        # if middleware is set
        if (count(static::$middlewares)) {
            $properties["middlewares"] = static::$middlewares;
        }

        # checking if url has already been registered
        if (static::$enable_subdomains) {
            if (static::$subdomain != null) {
                if (!array_key_exists($key, static::$domains[static::$subdomain][$method])) {
                    # register as new url path
                    static::$domains[static::$subdomain][$method][$key] = $properties;
                    return;
                }
            } else {
                if (!array_key_exists($key, static::$domains[static::$domain][$method])) {
                    # register as new url path
                    static::$domains[static::$domain][$method][$key] = $properties;
                    return;
                }
            }
        } else {
            if (!array_key_exists($key, static::$routes[$method])) {
                # register as new url path
                static::$routes[$method][$key] = $properties;
                return;
            }
        }


        # other wise throw double map error;
        # code...


    }

    static protected function patternHandler($lookup, $pattern, $uri, $method)
    {
        $path = $lookup[$pattern];

        # attaching the parameter values

        $splitPattern = explode("/", $pattern);
        $splitUri = explode("/", $uri);

        # get the intersect 
        $intersect = array_intersect($splitPattern, $splitUri);

        # get the diff between intersect and uri
        $params = array_diff($splitUri, $intersect);
        $p = [];

        foreach ($params as $key => $value) {
            array_push($p, $value);
        }

        #setting the parameter value
        $i = 0;
        foreach ($lookup[$pattern]["params"] as $key => $value) {
            $lookup[$pattern]["params"][$key] = $p[$i];
            $i++;
        }

        $request = new Request($method);
        $request->setParams($lookup[$pattern]["params"]);

        return static::listenHandler($lookup, $pattern, $request);
    }


    protected static function authorize($middleware, Request $request, $headers)
    {

        $type = null;

        if (strpos($middleware, ':')) {
            $xplode = explode(':', $middleware);
            $middleware = $xplode[0];
            $type = $xplode[1];
        }

        if ($middleware == 'Authorization' || $middleware == 'authorization') {

            $schema = new Schema();

            if (strtolower($type) == 'bearer') {

                $authToken = $headers['Authorization'] ?? $headers['authorization'];

                if (!empty($authToken)) {

                    $authToken = trim(preg_replace("/Bearer/", '', $authToken));
                    $authToken = trim(preg_replace("/bearer/", '', $authToken));
                    $authToken = (new Hash)->getDecodedBase($authToken);
                }

                $authUser = $schema->table('auth_access_tokens')->find('token', $authToken);

                if (!$authUser) {
                    return false;
                }

                $last_used_date = $authUser->last_used_date ?? $authUser->created_date;
                $last_date = Carbon::parse($last_used_date);
                $current_date = Carbon::now();

                $seconds = $last_date->diffInSeconds($current_date);

                if ($seconds > \App\Config\App::$token_expiration) {
                    return false;
                }

                $user = (new $authUser->token_type)->find($authUser->token_id);

                if (!$user) {
                    return false;
                }

                Auth::login($user);

                $schema->table('auth_access_tokens')->where('id', $authUser->id)->first()->update([
                    'last_used_date' => $request->timestamp()
                ]);
            }
        }

        return true;
    }

    protected static function listenHandler($lookup, $uri, $request)
    {

        $message = '';
        $action = 'next';
        $code = 200;

        $path = $lookup[$uri];

        if (isset($path['middlewares'])) {

            $headers = $request->getHeaders();
            $middlewares = $path['middlewares'];
            $responseFormat = $headers["Accept"] ?? null;

            foreach ($middlewares as $middleware) {

                if (preg_match('/authorization:(.*)/', strtolower($middleware))) {

                    if (!Route::authorize($path, $request, $headers)) {
                        $message = 'Unauthorized Request';
                        $code = 401;
                        $action = 'break';
                        break;
                    }

                    continue;
                }

                $middleware = new $middleware;
                $code = $middleware->status;
                $message = $middleware->message;

                $action = $middleware->handle($request, 'next');
            }

            if ($action !== 'next') {

                if ($responseFormat == 'application/json') {
                    return Response::json(['success' => false, 'message' => $message], $code);
                }

                return Response::content($message, $code);
            }
        }

        $controller_type = gettype($path["action"]);

        if ($controller_type == "string" || $controller_type == "array") {

            if ($controller_type == 'string') {

                $action_list = explode("::", $path["action"]);
                $controller = static::$controller_namespace . $action_list[0];
            } else {

                $action_list = $path['action'];
                $controller = $action_list[0];
            }

            //Call controller instance
            $handler_controller = new $controller;
            $handler_method = $action_list[1];

            if ($handler_controller instanceof BaseController) {

                return $handler_controller->$handler_method($request);
            } else {

                /**
                 * Throw Bad controller call exception. 
                 * 
                 * */
                if (($headers['Accept'] ?? null) == 'application/json') {

                    return Response::json([
                        "status" => 500,
                        "message" => "Controller [$handler_controller] is not a valid controller class.",
                        "error" => (new \Exception("Bad Controller call [$handler_controller] is not a valid controller class.")),
                    ], 500);
                } else {
                    throw new \Exception("Bad Controller call [$handler_controller] is not a valid controller class.");
                }
            }
        } else {
            $action = $path["action"];
            return call_user_func($action, $request);
        }
    }

    static public function listen()
    {

        $uri = !empty($_SERVER["REQUEST_URI"]) ? trim($_SERVER["REQUEST_URI"], "/") : "";
        $method = strtolower($_SERVER["REQUEST_METHOD"]);
        $domain = $_SERVER['HTTP_HOST'];

        if ($method == "options") {
            return header("HTTP/1.1 200 Ok");
        }

        static::$route_lookup_list = static::$routes[$method];

        if (static::$enable_subdomains) {
            $domain = str_replace("www.", "", $domain);

            // Do some domain name checks here
            if (array_key_exists($domain, static::$domains)) {
                static::$route_lookup_list = static::$domains[$domain][$method];
            } else {
                if (array_key_exists("*." . static::$domain, static::$domains)) {
                    $wild_card_enabled = true;
                }
            }
        }

        if (preg_match('/\?/i', $uri)) {
            $uri = preg_replace("/\?(.*)/", "", $uri);
        }

        # if uri is emty
        if (empty($uri)) {
            $uri = "index";
        } else {
            $uri = "index/" . $uri;
        }


        /**
         * if uri is registered in domain lookup method list
         */
        if (array_key_exists($uri, static::$route_lookup_list) && !isset($wild_card_enabled)) {
            echo static::listenHandler(static::$route_lookup_list, $uri, new Request($method));
        }
        /**
         * Checking wildcard domains if they are allowed
         */
        else if (static::$enable_subdomains && isset($wild_card_enabled)) {
            $wildcard = "*." . static::$domain;
            if (array_key_exists($wildcard, static::$domains)) {

                $request = new Request($method);
                $request->_domain = $domain;
                $request->_subdomain = explode(".", $domain)[0];

                static::$route_lookup_list = static::$domains[$wildcard][$method];
                if (array_key_exists($uri, static::$route_lookup_list)) {
                    echo static::listenHandler(static::$route_lookup_list, $uri, $request);
                }
            }
        } else {
            # verify if the url pattern is registered
            # for url that have parameters
            $pattern = Route::verifyPattern($uri, $method);

            # checking it pattern exists
            if (array_key_exists($pattern, static::$route_lookup_list)) {
                echo static::patternHandler(static::$route_lookup_list, $pattern, $uri, $method);
                return;
            }


            if ($method === "post") {

                $headers = (new Request($method))->getHeaders();

                if (($headers['Accept'] ?? null) == 'application/json') {

                    echo Response::json([
                        "status" => 500,
                        "message" => "Invalied post request, this request is not handled by this app!!",
                        "error" => (new \Exception('Unhandled Post Request has been initiated!!')),
                    ], 500);
                } else {
                    throw new \Exception('Unhandled Post Request has been initiated!!');
                }

                return;
            }

            echo Response::error404();
        }
    }

    static public function pattern()
    {

        $registerer = static::$routes;

        if (static::$enable_subdomains) {
            $registerer = static::$domains;
        }
        echo json_encode($registerer);
    }

    static private function interogate($url)
    {
        # cleaning the url
        $clean = !empty($url) ? trim($url, "/") : $url;

        # if empty url [key will be 'index']
        if (empty($clean)) {
            return "index";
        }

        # if not trailing slash anymore but has param identifier [:]
        else if (!preg_match("/\//", $clean) && strpos($clean, ":")) {
            $clean = "index/" . $clean;
            $pp = Route::createPP($clean);
            return $pp;
        }

        # if param identifier [:]  exists in url
        else if (preg_match("/\//", $clean) && strpos($clean, ":")) {
            $pp = Route::createPP($clean);
            return $pp;
        }

        return $clean;
    }

    static private function createPP($clean)
    {
        $split = explode("/", $clean);
        $base = $split[0];
        $params = [];

        for ($i = 1; $i < count($split); $i++) {
            $path = $split[$i];
            if (strpos($path, "{") > -1 && strpos($path, ":") > -1 && strpos($path, "}") > -1) {
                $param = str_replace("{", "", str_replace("}", "", $path));

                $pS = explode(":", $param);
                $base .= "/~" . $pS[1];

                $key = $pS[0];
                if (array_key_exists($key, $params)) {
                    throw new Exception("Duplicate entry key for url parameter[" . $key . "]", 0);
                    exit;
                }
                $params[$key] = null;
            } else {
                $base .= "/" . $path;
            }
        }

        return [$base, $params];
    }

    static public function verifyPattern($uri, $method)
    {
        $split = explode("/", $uri);
        $base = '';

        $sub = '';
        $params = [];

        $j = 1;
        $numberRegisterUrl = count(static::$route_lookup_list);
        $pattern = '';

        while (true) {

            if ($j == count($split)) {
                break;
            }

            # get all base path 
            for ($b = 0; $b < $j; $b++) {
                $path = $split[$b];
                $base .= $path . "/";
            }

            #get all parameters as sub path
            for ($i = $j; $i < count($split); $i++) {
                $param = $split[$i];
                $type = (is_numeric($param)) ? "int" : "string";
                $sub .= "/~" . $type;
            }


            # merge base and sub path together
            $pattern = !empty($base) ? trim($base, "/") . $sub : $base . $sub;

            # check if pattern exists
            if (array_key_exists($pattern, static::$route_lookup_list)) {
                break;
            }

            # if path does not exists and out of count
            // if ($j == ($numberRegisterUrl)) {
            //     break;
            // }

            # all variables to be back to initial state 
            # when j is to increment
            $pattern = "";
            $base = "";
            $sub = "";

            $j++;
        }

        # checking on index thats has parameters 
        // if(empty($pattern)) 
        // {
        //     $uri = "index/".$uri;
        //     $pattern = Route::verifyPattern($uri, $method);
        // }

        return $pattern;
    }


    /**
     * Bundle all route files create by devs
     * 
     *  @param $routes - Specify routes to be loaded
     *  Default null, and bundles all custom route files by default.
     *  Array value required to specify routes to be loaded
     * 
     * */

    public static function loadRoutes(array|null $routes = null)
    {

        if (!is_null($routes)) {

            foreach ($routes as $route) {
                $route_path = __DIR__ . "/../routes/" . $route . ".php";
                if (file_exists($route_path)) {
                    $renew = $route_path;
                    static::registerRoute($route_path);
                    return $renew;
                }
            }
        } else {
            foreach (glob("./../routes/*.php") as $route) {
                if (!preg_match('/route.php/', $route) && static::routeIsNotLoaded($route)) {
                    require $route;
                    static::registerRoute($route);
                }
            }
        }
    }


    /**
     *  Register all loaded routes in order not to reload
     * 
     *  @param $routes - Specify routes to be registered
     * 
     * */

    public static function registerRoute(string $route)
    {

        array_push(static::$registered_routes, $route);
    }


    /**
     *  Verifies if route has not been loaded ealier in other files
     * 
     *  @param $routes - Specify routes to be verified
     * 
     * */
    public static function routeIsNotLoaded(string $route)
    {

        if (!in_array($route, static::$registered_routes)) {
            return true;
        }

        return false;
    }

    static public function route_modifier($map)
    {

        $route = (new Route);
        $route->map = $map;

        return $route;
    }

    private function set_route_name($name)
    {

        if (isset($_ENV['app_route_name_specifier'])) {
            $names = $_ENV["app_route_name_specifier"];
            if (!array_key_exists($name, $names)) {
                $_ENV['app_route_name_specifier'][$name]  = $this->map;
            }
        } else {
            $_ENV['app_route_name_specifier'] = [$name => $this->map];
        }
    }
}
