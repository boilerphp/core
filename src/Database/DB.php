<?php

namespace Boiler\Core\Database;


final class DB
{

    public function __construct(string $connection = null)
    {
        return (new Schema($connection));
    }

    public static function query($table)
    {
        $instance = new Schema;
        $instance->table($table);
        return $instance->connection()->createQueryBuilder()->from($instance->getTableName());
    }

    public static function table($table)
    {
        $instance = new Schema;
        return $instance->table($table);
    }
}
