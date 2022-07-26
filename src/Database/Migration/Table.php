<?php

namespace App\Core\Database\Migration;

use App\Core\Database\Schema;


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


    /**
     * Database Connection key
     *
     * @var App\Core\Database\Schema
     *
     */
    protected static $schema;



    public function __construct($key = null)
    {
        static::$dbkey = $key;

        static::$schema = static::getSchema();
    }


    public static function getSchema()
    {

        static::$schema = new Schema();
        static::$schema->connection(static::$dbkey);

        return static::$schema;
    }


    public static function connection($name)
    {
        return new Table($name);
    }

    public static function create($name, $callback)
    {
        $diagram = new Diagram($name);
        $callback($diagram);
        $tableQuery = $diagram->createTableQuery(
            $diagram->trimmer($diagram->query),
            $diagram->trimmer($diagram->primary_keys)
        );

        $foreignKeysQuery = $diagram->foreignKeyProccessor($name);
        Table::createAlters($foreignKeysQuery);
        static::getSchema()->query($tableQuery);
    }

    public static function modify($name, $callback)
    {

        $diagram = new Diagram($name);
        $diagram->setPkMode(false);

        $callback($diagram);
        $query = $diagram->modifyTableQuery(
            $diagram->trimmer($diagram->query),
            $diagram->trimmer($diagram->primary_keys)
        );

        $foreignKeysQuery = $diagram->foreignKeyProccessor($name);
        Table::createAlters($foreignKeysQuery);

        static::getSchema()->query($query);
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

        static::getSchema()->dropDatabaseTable($table);
    }
}
