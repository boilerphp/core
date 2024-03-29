<?php 

namespace Boiler\Core\Middlewares;

class Session
{

    protected static $configs = [];


    public function initialize() 
    {
        
        # User's Configs
        // static::loadConfigs();

        static::start();
    }
    
    public static function config($key, $value)
    {
        static::$configs[$key] = $value;
    }

    public static function end($name) 
    {
        unset($_SESSION[$name]);
    }

    public static function exists($name) 
    {
        
        if(isset($_SESSION[$name])) 
        {
            return true;
        }

        return false;
    }

    public static function start()
    {
        session_start();
    }
    
    public static function set($name, $value) 
    {
        $_SESSION[$name] = $value;
    }

    public static function get($name) 
    {

        if(isset($_SESSION[$name])) 
        {

            return $_SESSION[$name];
        } 

        return false;
    }

    public static function clear() 
    {

        $_SESSION == null; 
        if(session_destroy()) { return true; }

    }

    private static function loadConfigs()
    {
        foreach(static::$configs as $key => $value)
        {
            ini_set($key, $value);
        }
    }
}

