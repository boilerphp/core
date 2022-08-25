<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Configs\GlobalConfig;
use Boiler\Core\Database\Connection;
use Boiler\Core\Database\Schema;


class Table implements MigrationInterface
{


    /**
     * All alter querys from table contructions
     *
     * @var array
     *
     */

    public static $alters = array();


    /**
     * Database Connection key
     *
     * @var string
     *
     */
    protected static $database;


    /**
     * Database connection 
     *
     * @var Boiler\Core\Database\Connection|null
     *
     */
    protected static $connection;


    public function __construct(Connection $connection = null)
    {
        static::$connection = $connection ?? static::getSocket();
    }

    public static function getSocket()
    {
        if (static::$database !== null && static::$database !== 'default') {
            
            return GlobalConfig::getTargetConnection(static::$database);
        } else {

            if (!GlobalConfig::$IS_CONNECTED) {
                GlobalConfig::setAppConnetion();
            }

            return GlobalConfig::getAppConnection();
        }
    }


    public static function connection($name)
    {   
        if(env('APP_ENV')  == 'testing' && env('DB_CONNECTION') == 'sqlite') {
            setEnv("DB_DATABASE", $name.".sqlite");
        }

        static::$database = $name;
        $connection = static::getSocket();
        return new Table($connection);
    }

    public static function create($name, $callback)
    {
        $driver = GlobalConfig::getAppConnection()->getDriver();
        $diagram = new Diagram($name, $driver);
        
        $callback($diagram);
        $tableQuery = $diagram->createTableQuery(
            $driver,
            trimmer($diagram->dataTypes()->getQuery(), ","),
            trimmer($diagram->dataTypes()->getPrimaryKeys(), ",")
        );

        $foreignKeysQuery = $diagram->dataTypes()->foreignKeyProccessor($name);
        Table::createAlters($foreignKeysQuery);

        (new Schema(static::$connection))->query($tableQuery);
    }

    public static function modify($name, $callback)
    {

        $diagram = new Diagram($name, GlobalConfig::getAppConnection()->getDriver());
        $diagram->setPkMode(false);

        $callback($diagram);
        $query = $diagram->modifyTableQuery(
            trimmer($diagram->dataTypes()->getQuery(), ","),
            trimmer($diagram->dataTypes()->getPrimaryKeys(), ",")
        );

        $foreignKeysQuery = $diagram->dataTypes()->foreignKeyProccessor($name);
        Table::createAlters($foreignKeysQuery);

        (new Schema(static::$connection))->query($query);
    }

    private static function createAlters($foreignKeysQuery)
    {
        if ($foreignKeysQuery != "" && !is_null($foreignKeysQuery)) {
            array_push(static::$alters, $foreignKeysQuery);
        }
    }

    public static function getAlters()
    {
        return static::$alters;
    }

    public static function dropIfExists($table)
    {
        (new Schema(static::$connection))->query("DROP TABLE IF EXISTS $table");
    }
}
