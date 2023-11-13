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

    protected static $alters = array();


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
        if (env('APP_ENV')  == 'testing' && env('DB_CONNECTION') == 'sqlite') {
            setEnv("DB_DATABASE", $name . ".sqlite");
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
        $foreignKeysQuery = $diagram->dataTypes()->foreignKeyProccessor($name);

        $query = $diagram->createTableQuery(
            $diagram->dataTypes()->getQuery(),
            $diagram->dataTypes()->getPrimaryKeys(),
            (in_array($driver, ["sqlite", "pdo_sqlite"])) ? $foreignKeysQuery : null
        );

        if (!in_array($driver, ["sqlite", "pdo_sqlite"])) {
            Table::createAlters($foreignKeysQuery);
        }

        (new Schema(static::getConnection()))->query($query);
    }

    public static function modify($name, $callback)
    {
        $driver = GlobalConfig::getAppConnection()->getDriver();
        $diagram = new Diagram($name, $driver);
        $diagram->dataTypes()->setPkMode(false);

        $callback($diagram);
        $foreignKeysQuery = $diagram->dataTypes()->foreignKeyProccessor($name);

        $query = $diagram->modifyTableQuery(
            $diagram->dataTypes()->getQuery(),
            $diagram->dataTypes()->getPrimaryKeys()
        );

        if (in_array($driver, ["sqlite", "pdo_sqlite"])) {
            if ($query !== null) {
                if (preg_match('/\; ALTER TABLE/', $query)) {
                    $queries = explode(';', $query);
                    foreach ($queries as $query) {
                        (new Schema(static::getConnection()))->query($query);
                    }

                    $query = null;
                }
            }
        }

        if ($foreignKeysQuery) {
            Table::createAlters($foreignKeysQuery);
        }

        if ($query !== null) {
            (new Schema(static::getConnection()))->query($query);
        }
    }

    public static function renameTable($old_name, $new_name)
    {

        $driver = GlobalConfig::getAppConnection()->getDriver();
        $diagram = new Diagram($old_name, $driver);

        $query = $diagram->renameTableQuery($new_name);
        (new Schema(static::getConnection()))->query($query);
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

    public static function getConnection()
    {
        return static::$connection;
    }

    public static function dropIfExists($table)
    {
        (new Schema(static::$connection))->dropTable($table);
    }
}
