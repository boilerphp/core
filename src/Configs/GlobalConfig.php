<?php

namespace Boiler\Core\Configs;

use Boiler\Core\Database\Connection;

class GlobalConfig
{

    /** 
     * Holds the list of appsettings configuration objects
     * 
     * @var object
     * 
     */
    static protected $appConfigs;


    /** 
     * database connection object
     * 
     * 
     */
    static public $CONNECTION;

    /** 
     * Holds the list of database connection objects
     * 
     * @var array
     * 
     */
    static public $connectionList = [];


    /** 
     * Holds the application database connection state
     * @boolean - false
     * 
     * becomes true on the datbase is connected.
     */
    static public $IS_CONNECTED = false;


    /** 
     * Holds the connection of the target database 
     * 
     * 
     */
    static protected $targetDatabase;


    /** 
     * Get app configs 
     * 
     * 
     */
    static function getAppConfigs($key = null)
    {
        if ($key !== null) {
            if (isset(static::$appConfigs->$key)) {
                return static::$appConfigs->$key;
            }
        }

        return static::$appConfigs;
    }


    /** 
     * set app configs 
     * 
     * 
     */
    static function setAppConfigs($configs)
    {
        static::$appConfigs = static::configurationDataAssessment($configs);
    }


    static function configurationDataAssessment($configs)
    {

        $configurations = $configs;

        foreach ($configs as $key => $value) {
            if (is_object($value)) {
                $configurations->$key = static::configurationDataAssessment($value);
            } else if (is_string($value)) {
                if (preg_match('/\$\{(.*)\}/', $value)) {
                    $env_key = preg_replace('/\$\{(.*)\}/', '$1', $value);
                    $configurations->$key = env($env_key);
                }
            }
        }

        return $configurations;
    }

    /** 
     * Get app connections 
     * 
     * 
     */
    static function getAppConnection()
    {
        if(static::$targetDatabase !== null) {
            return static::$targetDatabase;
        }
        
        return static::$CONNECTION;
    }

    /** 
     * Set app connections 
     * 
     * 
     */
    static public function setAppConnetion()
    {
        static::$CONNECTION = (new Connection);
        static::$CONNECTION->connect();

        static::$IS_CONNECTED = true;
    }

    static public function setTarget($target)
    {

        static::$targetDatabase = (new Connection);
        static::$targetDatabase->setTarget($target);
        static::$targetDatabase->connect();

        return static::$targetDatabase;
    }

    static public function closeConnection()
    {

        if (static::$CONNECTION != null) {
            static::$CONNECTION->closeConnection();
        }

        if (static::$targetDatabase != null) {
            static::$targetDatabase->closeConnection();
        }

        $CONNECTION = null;
        $targetDatabase = null;
    }
}
