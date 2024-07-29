<?php

namespace Boiler\Core\Engine\Router;

class Request extends Validator
{

    /**
     * URL 
     *
     * @var array
     */
    protected $_url;

    /**
     * domain name
     *
     * @var string
     */
    public $_domain;

    /**
     * subdomain name
     *
     * @var string
     */
    public $_subdomain;

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
     * request data bag
     *
     * @var array
     */
    protected $dataBag = [];


    /**
     * request headers
     *
     * @var array
     */
    protected $headers = [];


    /**
     * set the method use in http request
     *
     * @param string $method
     * @return void
     */
    public function __construct($method)
    {
        $this->method = strtoupper($method);
        $this->init();
    }


    protected function init()
    {
        $this->setHeaders();

        switch (strtolower($this->method)) {
            case 'get':
                $this->map($_GET);
                break;

            case 'post':
                $this->map($_POST);
                break;

            case 'patch':
                $this->map($_POST);
                break;

            case 'put':
                $this->map($_POST);
                break;

            case 'delete':
                $this->map($_GET);
                break;
        }

        foreach ($_FILES as $key => $value) {
            $this->$key = $value;
        }
    }

    protected function setHeaders()
    {
        $this->headers = getallheaders();
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    protected function jsonMap()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->dataBag[$key] = $value;
                $this->$key = $value;
            }
        }
    }

    public function all()
    {
        return $this->dataBag;
    }

    public function exist($key)
    {
        if (isset($this->$key) || array_key_exists($key, $this->dataBag)) {
            return true;
        }

        return false;
    }


    public function hasKey($key)
    {
        return $this->exist($key);
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


    public function getValue($name)
    {
        return $this->input($name);
    }


    public function input($name)
    {
        return $this->entry($name);
    }


    protected function entry($key)
    {
        return ($this->exist($key)) ? $this->dataBag[$key] : null;
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
        $accept = $this->headers['Content-Type'] ?? null;
        if ($accept === 'application/json') {
            $this->jsonMap();
        }

        $this->dataBag = array_merge($this->dataBag, $data);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function loadData($data)
    {
        $this->dataBag = $data;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
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
