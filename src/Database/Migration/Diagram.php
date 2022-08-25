<?php

namespace Boiler\Core\Database\Migration;


class Diagram extends ColumnDefination {

    /**
    * App formatted query
    *
    * @var string
    *
    */

    public $query;
    

    /**
    * primary key query
    *
    * @var string
    *
    */
    public $primary_keys;


    public function __construct(protected string $table, protected string $driver)
    {
        parent::__construct($driver);
    }
    
    public function createTableQuery($driver, $columns, $primary_keys) 
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