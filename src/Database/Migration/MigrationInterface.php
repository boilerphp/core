<?php

namespace Boiler\Core\Database\Migration;

interface MigrationInterface {


    public static function create($name, $callback);

}