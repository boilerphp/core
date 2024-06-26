<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Database\Schema;

class Diagram extends ColumnDefination {

    protected $TableQuery;

    public function __construct(protected string $table, protected string $driver, protected Schema $schema)
    {
        parent::__construct($table, $driver, $schema);
    }
    
    public function createTableQuery($columns, $primary_keys, $foreign_keys = null) 
    {
        $this->TableQuery = "CREATE TABLE IF NOT EXISTS `$this->table` ($columns";
        if($primary_keys != "") {
            $this->TableQuery .= ", PRIMARY KEY ($primary_keys)";
        }

        if($foreign_keys !== null) {
            $this->TableQuery .= ", $foreign_keys";
        }
        
        $this->TableQuery .= " )";
        return $this->TableQuery;
    }

    public function modifyTableQuery($columns, $primary_keys) {

        if($columns) {
            $this->TableQuery = "ALTER TABLE `$this->table` $columns";
            if($primary_keys != "") { $this->TableQuery .= ", ADD PRIMARY KEY ($primary_keys)"; }

            return $this->TableQuery;
        }

        return null;
    } 

    public function renameTableQuery($new_name) {

        $this->TableQuery = "ALTER TABLE `$this->table` RENAME TO $new_name";
        return $this->TableQuery;
    }
}