<?php

namespace Boiler\Core\Engine\Router;

class Request extends Validator
{

    /**
     * URL parameters 
     *
     * @var array
     */
    protected $_params;

    /**
     * request method
     *
     * @var string
     */
    public $method;

    /**
     * request location
     *
     * @var string
     */
    public $location;

    /**
     * request url
     *
     * @var string
     */
    public $url;

    /**
     * set the method use in http request
     *
     * @param string method of http request action
     * @return void
     */
    public function __construct($method)
    {

        $this->method = strtoupper($method);
        $this->init($method);
    }


    protected function init($method)
    {

        switch ($method) {
            case 'get':
                $this->get();
                break;

            case 'post':
                $this->post();
                break;

            case 'patch':
                $this->patch();
                break;

            case 'put':
                $this->put();
                break;

            case 'delete':
                $this->delete();
                break;
        }

        foreach ($_FILES as $key => $value) {
            $this->$key = $value;
        }
    }


    public function headers()
    {
        return $this->setHeaders();
    }


    public function getHeaders()
    {
        return $this->setHeaders();
    }

    protected function setHeaders()
    {
        $headers = [];

        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }

        return $headers;
    }


    public function json($key = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!is_null($key)) {
            if (isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        }

        if(is_array($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }

        return $data;
    }


    public function all()
    {
        $data = [];
        if ($this->method == 'GET') {
            $data = $_GET;
        } else if ($this->method == 'POST') {
            $data = $_POST;
        }

        if(!$data && $this->json()) {
            $data = $this->json();
        }

        return $data;
    }

    public function exist($key)
    {
        if (isset($this->$key) || $this->json($key)) {
            return true;
        }

        return false;
    }

    public function hasKey($key)
    {
        if (isset($this->$key)) {
            return true;
        }

        return false;
    }

    public function hasParam($key)
    {
        if (isset($this->_params[$key])) {
            return true;
        }

        return false;
    }


    public function param($key)
    {

        if ($this->hasParam($key)) {
            return $this->_params[$key];
        }

        return null;
    }


    public function without($keys)
    {

        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }


    protected function get()
    {
        $this->map($_GET);
    }


    protected function post()
    {
        $this->map($_POST);
    }

    protected function put()
    {
        $this->map($_POST);
    }

    protected function patch()
    {
        $this->map($_POST);
    }

    protected function delete()
    {
        $this->map($_POST);
    }

    public function file($name)
    {
        if (isset($_FILES[$name])) {
            if (is_array($_FILES[$name]["name"])) {

                $filelist = [];
                foreach ($_FILES[$name]["name"] as $key => $value) {
                    $filelist[] = [
                        "name" => $value,
                        "tmp_name" => $_FILES[$name]["tmp_name"][$key],
                        "size" => $_FILES[$name]["size"][$key],
                        "full_path" => $_FILES[$name]["full_path"][$key],
                        "type" => $_FILES[$name]["type"][$key],
                        "error" => $_FILES[$name]["error"][$key]
                    ];
                }

                return json_decode(json_encode($filelist));
            }

            return json_decode(json_encode($_FILES[$name]));
        }

        return null;
    }


    public function filename($name)
    {
        if ($this->file($name)) {
            return $this->file($name)->name;
        } else {
            return null;
        }
    }


    protected function map($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        $this->json();
    }


    public function timestamp()
    {
        return date("Y-m-d H:i:s");
    }


    public function location()
    {
        return trim($_SERVER["REQUEST_URI"], "/");
    }


    public function url($name = null)
    {
        $this->_url = $this->location();

        if ($name != null) {
            if ($this->_url == $name) {
                return true;
            } else {
                return false;
            }
        } else {
            return $this->_url;
        }
    }

    /**
     * gets the ip address of the request browser.
     *
     * @return string $ip
     */
    public static function ipAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function setParams($params)
    {
        $this->_params = $params;
    }
}
