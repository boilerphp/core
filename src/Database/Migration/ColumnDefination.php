<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Database\Migration\DataTypes\MySqlMigrationDataTypes;
use Boiler\Core\Database\Migration\DataTypes\SqlLiteMigrationDataTypes;
use Boiler\Core\Database\Schema;
use Boiler\Core\Exceptions\DriverNotSupportedException;
use Exception;
use Reflection;
use ReflectionClass;

class ColumnDefination
{

    protected $dataTypeClass;


    protected $schema;
    

    protected $driverDataTypeMap = [
        "sqlite" => SqlLiteMigrationDataTypes::class,
        "pdo_sqlite" => SqlLiteMigrationDataTypes::class,
        "mysqli" => MySqlMigrationDataTypes::class,
        "pdo_mysql" => MySqlMigrationDataTypes::class,
    ];

    public function __construct(protected string $table, protected string $driver)
    {
        if (array_key_exists($this->driver, $this->driverDataTypeMap)) {

            $this->dataTypeClass = new $this->driverDataTypeMap[$this->driver];
            $this->schema = new Schema();
            $this->dataTypes()->setTable($table);
            return;
        }

        // throw driver not supported error.
        throw new DriverNotSupportedException();
    }

    public function id($name = "id")
    {
        $this->dataTypes()->id($name);
    }

    public function dataTypes()
    {
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

    public function addColumn($name)
    {
        $mode = 'ADD ';
        $query = $this->dataTypes()->getQuery();

        if (preg_match('/ADD/', $query)) {

            $this->dataTypes()->trimQuery();

            if ($this->driver === "sqlite") {
                $mode = "; ALTER TABLE $this->table ADD ";
            } else {
                $mode = ", ADD ";
            }
        }

        $this->dataTypes()->setColumnWithPreffix($mode. "`$name`");
        $this->dataTypes()->setKeyName($name);
        return $this->dataTypes();
    }

    public function changeColumnName($current_name, $new_name)
    {
        if ($this->driver == "sqlite") {
            return $this->renameColumn($current_name, $new_name);
        }

        $this->dataTypes()->setColumnWithPreffix(concat(["CHANGE", "`$current_name`", "`$new_name`"]));
        $this->dataTypes()->setKeyName($new_name);
        return $this->dataTypes();
    }

    public function renameColumn($current_name, $new_name)
    {
        $query = "ALTER TABLE `$this->table` RENAME COLUMN `$current_name` TO `$new_name`";
        $this->schema->query($query);

        return $this;
    }

    public function dropColumn($columns)
    {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $query = "ALTER TABLE `$this->table` DROP COLUMN `$column`;";
                $this->schema->query($query);
            }
            return;
        } else {
            $query = "ALTER TABLE `$this->table`DROP COLUMN `$columns`";
            $this->schema->query($query);
        }
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
        if ($this->driver == "sqlite") {
            return null;
        }

        (new Schema)->query(concat(["ALTER TABLE `$this->table` DROP INDEX", "`$name`"]));
    }

    public function timestamps()
    {
        if ($this->driver === "sqlite") {
            $this->column("created_date")->timestamp()->nullable();
            $this->column("updated_date")->timestamp()->nullable();

            return;
        }

        $this->column("created_date")->timestamp()->default('CURRENT_TIMESTAMP()');
        $this->column("updated_date")->timestamp()->default('CURRENT_TIMESTAMP()');
    }

    public function __call($name, $arguments)
    {

        $dataTypeClass = get_class($this->dataTypeClass);
        $clReflection = new ReflectionClass($dataTypeClass);

        $methods = [];

        foreach ($clReflection->getMethods() as $clMethod) {
            $methods[] = $clMethod->name;
        }

        if (!in_array($name, $methods)) {

            /** 
             * This will throe an exception if method not found in the data class.
             */

            throw new Exception("Undifined method [$name] not in $dataTypeClass of migration file.", 1);
        }

        return $this;
    }
}
