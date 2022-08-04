<?php

namespace Boiler\Core;

use App\Config\App;
use App\Config\ViewsConfig;
use Asm89\Stack\CorsService;
use Boiler\Core\Middlewares\Session;
use Boiler\Core\Engine\Router\Route;
use Boiler\Core\Configs\GlobalConfig;
use Boiler\Core\Engine\Router\Response;
use Boiler\Core\FileSystem\Fs;

class Server extends App
{

    protected $configurations = null;


    public function __construct(protected $debug = true)
    {
        /**
         * Ignition for app custom configurations
         */
        $this->ignition();


        if ($this->getAppCongigurations()) {

            if ($this->configurations != null) {

                $this->setEnv();
                GlobalConfig::setAppConfigs($this->configurations);
            }
        }

        /**
         * set app debug state
         */
        $this->debug = (bool) env('APP_DEBUG');

        /**
         * boot required middlewares and headers
         */
        $this->boot();
    }

    public function boot()
    {

        if (env('APP_ENV') !== 'testing') {

            $this->sessionConfigs();
            (new Session)->initialize();

            $this->loadHeaders();
        }
    }


    public function sessionConfigs()
    {

        # App Configs
        // ini_set('session.cookie_domain', $this->cookie_subdomain);
        ini_set('session.cookie_lifetime', $this->session_lifetime);
        ini_set('session.gc_maxlifetime', $this->session_lifetime);
    }

    public function getAppCongigurations()
    {

        if (file_exists(__DIR__ . "/../../../../appsettings.json")) {

            $this->configurations = json_decode(file_get_contents(__DIR__ . "/../../../../appsettings.json"));
            return true;
        }


        throw new \Exception('App configurations file is missing!');
        return false;
    }

    public function loadHeaders()
    {
        header("HTTP/1.1 200 OK");

        foreach ($this->headers as $key => $value) {

            if (count($this->allowed_domains) > 0) {
                if ($key == 'Access-Control-Allow-Origin') {

                    if (isset($_SERVER['HTTP_ORIGIN'])) {

                        $referrer = $_SERVER['HTTP_ORIGIN'];
                        $referrer = preg_replace('/http(.*)\:\/\/(.*)\/(.*)/', 'http$1://$2', $referrer);
                        if (in_array($referrer, $this->allowed_domains)) {
                            $header = ($key . ': ' . $referrer);
                            header($header);
                        }
                    }
                }
            } else {
                $header = ($key . ': ' . $value);
                header($header);
            }
        }

    }

    public function setEnv()
    {
        if (file_exists(__DIR__ . "/../../../../.env")) {
            $get_env_file = fopen(__DIR__ . "/../../../../.env", "r");
            if ($get_env_file) {
                while (!feof($get_env_file)) {

                    $line = fgets($get_env_file);
                    if (!preg_match('/^\#(.*)/', $line)) {
                        $key_value = explode("=", $line);

                        if (isset($key_value[0]) && isset($key_value[1])) {
                            $key = trim($key_value[0], " ");
                            $_ENV[$key] = trim($key_value[1], " ");
                        }
                    }
                }
            }
        }
    }

    public function initRouteHandler()
    {
        require __DIR__ . "/../../../../routes/route.php";

        // Route::pattern();
        Route::listen();
    }

    public function start($terminal = false)
    {
        try {

            /*
            * Checks if subdomains is enable and configures 
            * app for subdomain urls
            *
            */
            if (Route::$enable_subdomains) {
                Route::configure();
            }

            if ($terminal === false) {
                /*
                * Initialize route handler
                */
                $this->initRouteHandler();


                /*
                * Close Connection on instance
                */
                GlobalConfig::closeConnection();
            }
        } catch (\Exception $ex) {

            $response = Response::responseFormat();

            if ($this->debug === true) {

                if ($response === "application/json") {

                    return Response::json([
                        "error" => $ex->getMessage(),
                        "line" => "Line {$ex->getLine()} of {$ex->getFile()}",
                        "trace" => $ex->getTrace()
                    ], 500);
                }

                return absolute_view(
                    path: ["extension" => "php", "fullpath" => __DIR__ . "/errors/debug.php"],
                    data: ["ex" => $ex],
                    status: 500
                );
            } else {

                if ($response === "application/json") {

                    return Response::json([
                        'status' => 500,
                        'message' => 'Server Error'
                    ], 500);
                }

                if (Fs::exists(__DIR__ . '/../' . ViewsConfig::$views_path . '/errors/500.fish.php')) {
                    return view('errors/500');
                }

                return absolute_view(
                    path: ["extension" => "php", "fullpath" => __DIR__ . "/errors/500.php"],
                    status: 500
                );
            }
        }
    }
}
