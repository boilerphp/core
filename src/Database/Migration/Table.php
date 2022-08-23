<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Configs\GlobalConfig;
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
    protected static $dbkey = "default";



    public function __construct($key = null)
    {
        static::$dbkey = $key;
    }


    public static function connection($name)
    {
        if (!is_null($name)) {
            GlobalConfig::setTarget($name);
        }

        return new Table($name);
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

        (new Schema)->query($tableQuery);
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

        (new Schema)->query($query);
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
        if (static::$dbkey == null) {
            static::$dbkey = "default";
        }

        (new Schema)->query("DROP TABLE IF EXISTS $table");
    }
}
