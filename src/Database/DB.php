<?php

namespace Boiler\Core\Database;


final class DB extends Schema {

    public function __construct(string $connection = null)
    {
        parent::__construct($connection);
    }
}