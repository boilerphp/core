<?php

namespace Boiler\Core;

use App\Config\App;
use App\Config\ViewsConfig;
use Boiler\Core\Middlewares\Session;
use Boiler\Core\Engine\Router\Route;
use Boiler\Core\Configs\GlobalConfig;
use Boiler\Core\Engine\Router\Response;
use Boiler\Core\FileSystem\Fs;
use Exception;

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
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new Exception($errstr, 0);
        });


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
        if (env("APP_ENV") === "testing")
            $envFile = file_exists(__DIR__ . "/../../../../.env.testing")
                ? __DIR__ . "/../../../../.env.testing" : null;
        else
            $envFile = file_exists(__DIR__ . "/../../../../.env")
                ? __DIR__ . "/../../../../.env" : null;

        if ($envFile !== null) {
            $get_env_file = fopen($envFile, "r");
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

            try {
                

                $first = $ex->getTrace()[0];
    
                $response = Response::responseFormat();
    
                if ($this->debug === true) {
    
                    if ($response === "application/json") {
    
                        echo json([
                            "error" => $first["args"][1],
                            "line" => "Line {$first["line"]} of {$first["file"]}",
                            "trace" => $ex->getTrace()
                        ], 500);
    
                        exit;
                    }
    
                    echo absolute_view(
                        path: ["extension" => "php", "fullpath" => __DIR__ . "/errors/debug.php"],
                        data: ["ex" => $ex],
                        status: 500
                    );
    
                } else {
    
                    if ($response === "application/json") {
    
                        echo json([
                            'status' => 500,
                            'message' => 'Server Error'
                        ], 500);
    
                        exit;
                    }
    
                    if (Fs::exists(__DIR__ . '/../' . ViewsConfig::$views_path . '/errors/500.fish.php')) {
                        echo view('errors/500', ["error" => $ex]);
    
                        exit;
                    }
    
                    echo absolute_view(
                        path: ["extension" => "php", "fullpath" => __DIR__ . "/errors/500.php"],
                        data: ["error" => $ex],
                        status: 500
                    );
                }

            } catch (\Exception $ex) {
                
                echo $ex->getMessage();
            }

        }
    }
}
