<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Database\Schema;

class Migration extends Schema
{

    protected $table = "migrations";


    public int $version = 0;


    public function registerMigration(array $data)
    {

        if ($data) {
            if ($this->insertQuery($data, $this->table)) {

                $statement = $this->connection()->prepare($this->getSql());
                if ($statement->executeQuery($this->parameters)) {
                    return true;
                }

                return null;
            }
        }

        return false;
    }

    public function checkMigrationExists(array $data)
    {

        if ($data) {
            if ($this->insertQuery($data, $this->table)) {

                $statement = $this->connection()->prepare($this->getSql());
                if ($statement->executeQuery($this->parameters)) {
                    return true;
                }

                return null;
            }
        }

        return false;
    }
}
