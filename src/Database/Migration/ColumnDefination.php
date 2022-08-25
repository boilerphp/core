<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Database\Migration\DataTypes\MySqlMigrationDataTypes;
use Boiler\Core\Database\Migration\DataTypes\SqlLiteMigrationDataTypes;
use Boiler\Core\Database\Schema;

class ColumnDefination
{

    protected $dataTypeClass;


    protected $driverDataTypeMap = [
        "sqlite" => SqlLiteMigrationDataTypes::class,
        "pdo_sqlite" => SqlLiteMigrationDataTypes::class,
        "mysqli" => MySqlMigrationDataTypes::class,
        "pdo_mysql" => MySqlMigrationDataTypes::class,
    ]; 

    public function __construct(protected string $table, protected string $driver)
    {
        $this->dataTypeClass = new $this->driverDataTypeMap[$this->driver];
        if( in_array($this->driver, $this->driverDataTypeMap) ) {
            
            $this->dataTypes()->setTable($table);
            return;
        } 

        // throw driver not supported error.
    }

    public function id($name = "id") {
        $this->dataTypes()->id($name);
    }

    public function dataTypes() {
        return $this->dataTypeClass;
    }

    /**
     * Declaring the column name
     * 
     * @param $name - the name of the column 
     * 
     * @return Boiler\Core\Database\Migration\DataTypes\DataTypesInterface;
     */
    public function column($name)
    {

        $this->dataTypes()->setColumn($name);
        $this->dataTypes()->setKeyName($name);
        return $this->dataTypes();
    }

    public function after($column)
    {
        return $this->query = concat([trimmer($this->query, ","), "AFTER",  "`$column`"]);
    }

    public function addColumn($name)
    {

        $mode = 'ADD';

        if (!empty($this->query)) {
            if (preg_match('/ADD/', $this->query)) {
                $this->query = trimmer($this->query, ",");
                $mode = ', ADD';
            }
        }

        $this->dataTypes()->setColumnWithPreffix(concat([$mode, "`$name`"]));
        $this->dataTypes()->setKeyName($name);
        return $this->dataTypes();
    }

    public function changeColumnName($current_name, $new_name)
    {

        $this->column = concat(["CHANGE", "`$current_name`", "`$new_name`"]);
        $this->key = "$new_name";
        return $this->dataTypes();
    }

    public function dropColumn($columns)
    {
        $query = "";

        if (is_array($columns)) {
            foreach ($columns as $column) {
                $query .= "DROP `$column`, ";
            }
            $query = trim($query, ', ');
        } else {
            $query = "DROP COLUMN `$columns`";
        }

        (new Schema)->query("ALTER TABLE `$this->table` $query");
    }

    public function dropPrimaryKey()
    {
        (new Schema)->query("ALTER TABLE `$this->table` DROP PRIMARY KEY");
    }

    public function dropForeignKey($name)
    {
        (new Schema)->query(concat(["ALTER TABLE `$this->table` DROP FOREIGN KEY IF EXISTS", "`$name`"]));
    }

    public function dropConstraint($name)
    {
        (new Schema)->query(concat(["ALTER TABLE `$this->table` DROP CONSTRAINT IF EXISTS", "`$name`"]));
    }

    public function dropIndex($name)
    {
        (new Schema)->query(concat(["ALTER TABLE `$this->table` DROP INDEX IF EXISTS", "`$name`"]));
    }

    public function timestamps()
    {

        $this->column("created_date")->timestamp()->default("CURRENT_TIMESTAMP");
        $this->column("updated_date")->timestamp()->default("CURRENT_TIMESTAMP");
    }
}
