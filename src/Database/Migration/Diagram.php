<?php

namespace Boiler\Core\Database\Migration;


class Diagram extends ColumnDefination {


    public function __construct(protected string $table, protected string $driver)
    {
        parent::__construct($table, $driver);
    }
    
    public function createTableQuery($columns, $primary_keys) 
    {
        $this->TableQuery = "CREATE TABLE IF NOT EXISTS `$this->table` ($columns";
        if($primary_keys != "") {
            $this->TableQuery .= ", PRIMARY KEY ($primary_keys)";
        }
        
        $this->TableQuery .= " )";
        return $this->TableQuery;
    }

    public function modifyTableQuery($columns, $primary_keys) {

        $this->TableQuery = "ALTER TABLE `$this->table` $columns";
        if($primary_keys != "") { $this->TableQuery .= ", ADD PRIMARY KEY ($primary_keys)"; }
        return $this->TableQuery;
    } 
}