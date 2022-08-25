<?php

namespace Boiler\Core\Database\Migration\DataTypes;


abstract class AbstractMigrationDataTypes
{

    /**
     * default nullable contraint on datatypes
     *
     * @var string
     *
     */
    protected $nullable = "NOT NULL";

    /**
     * foreign keys query
     *
     * @var string
     *
     */

    protected $foreignKeys = "";

    /**
     * primary keys query
     *
     * @var string
     *
     */

    protected $primary_keys = "";

    /**
     * query map string
     *
     * @var string
     *
     */

    /**
     * primary key mode
     *
     * @var boolean
     *
     */
    protected $pk_mode = true;


    protected $query = "";
    
    
    private $table;


    /**
     * All columns mapping from table contructions
     *
     * @var array
     *
     */
    protected $columns = array();


    /**
     * All columns mapping from table contructions
     *
     * @var string
     *
     */
    protected $column;


    /**
     * Set column datatype to integer datatype
     * with auto increment value.
     * 
     * @return self
     */
    abstract public function increments();


    /**
     * Creates a column with name id and sets datatype to a 
     * big integer datatype with auto increment value.
     * 
     * @param $name - Default value 'id'
     * 
     * @return self
     */
    abstract public function id($name = "id");


    /**
     * Creates a unique column with name id and sets datatype to
     * big integer datatype with key and auto increment value. 
     * 
     * @param $name - Default value 'id'
     * 
     * @return self
     */
    abstract public function uniqueId($name = "id");


    /**
     * Creates a unique column with varchar datatype. 
     * 
     * @param $length - Default value '100'
     * 
     * @return self
     */
    abstract public function stringId($length = 100);


    /**
     * Set column datatype to integer
     * 
     * @param $length - Default value '9'
     * 
     * @return self
     */
    abstract public function integer($length = 9);


    /**
     * Set column datatype to varchar
     * 
     * @param $length - Default value '100'
     * 
     * @return self
     */
    abstract public function string($length = 100);


    /**
     * Set column datatype to text
     * 
     * @return self
     */
    abstract public function text();


    /**
     * Set column datatype to longtext
     * 
     * @return self
     */
    abstract public function longtext();

    /**
     * Define column as primary key
     * 
     * @return self
     */
    abstract public function primary($column = "");

    /**
     * Define default state of a column
     * If set 'true' value will be NULL
     * and if set 'false' value will be NOT NULL 
     * 
     * @param $state - Default value `true`
     * 
     * @return self
     */
    abstract public function nullable($state = true);

    /**
     * Set column datatype to time
     * 
     * @return self
     */
    abstract public function time();


    /**
     * Set column datatype to datetime
     * 
     * @return self
     */
    abstract public function timestamp();


    /**
     * Set column datatype to unique field
     * 
     * @return self
     */
    abstract public function unique();


    /**
     * Set column datatype to big integer
     * 
     * @param $length 
     * 
     * @return self
     */
    public function bigInteger($length = 20)
    {
        $this->query .= " $this->column BIGINT(" . (string) $length . "),";
        return $this;
    }

    /**
     * Set column datatype to unsigned big integer
     * 
     * @param $length 
     * 
     * @return self
     */
    public function unsignedBigInteger($length = 20)
    {
        $this->query .= " $this->column BIGINT(" . (string) $length . ") UNSIGNED,";
        return $this;
    }

    /**
     * Set column datatype to boolean
     * 
     * @return self
     */
    public function boolean()
    {
        $this->query .= " $this->column TINYINT(1),";
        return $this;
    }

    public function cascade()
    {
        $this->foreignKeys = trimmer($this->foreignKeys, ",");
        $this->foreignKeys .= "ON DELETE CASCADE ,";
        return $this;
    }


    /**
     * Set column datatype to date
     * 
     * @return self
     */
    public function date()
    {
        $this->query .= " $this->column DATE NOT NULL ,";
    }


    /**
     * Set column default value
     * 
     * @return self
     */
    public function default($value)
    {
        $this->query = trimmer($this->query, ",");
        $this->query .= " DEFAULT {$value},";
        return $this;
    }


    /**
     * Set column datatype to floating datatype
     * 
     * @param $length
     * @param $decimal 
     * 
     * @return self
     */
    public function float($length = 10, $decimal = 2)
    {
        $this->query .= " $this->column FLOAT(" . (string) $length . ", " . (string) $decimal . "),";
        return $this;
    }


    /**
     * Define a column as a foreign key column
     * and set the relationship keys.
     * 
     * @param $table - name of the foreign table
     * @param $reference - relating column of the foreign table
     * 
     * @return Boiler\Core\Database\ColumnDefination
     */
    public function foreign($table, $reference = "id")
    {
        if ($this->pk_mode) {
            $this->primary_keys .= " $this->key,";
        }
        $reference = is_null($reference) ? $this->key : $reference;

        $const = $table . "_" . $this->table . "_" . $this->key . "_fk";
        $this->foreignKeys .= " ADD CONSTRAINT `$const` FOREIGN KEY ($this->key) REFERENCES `$table` (`$reference`) ,";

        return $this;
    }


    public function foreignKeyProccessor($table)
    {
        if ($this->foreignKeys != "") {
            $query = trimmer($this->foreignKeys, ",");
            $alter_query = "ALTER TABLE $table " . $query;
            return $alter_query;
        }
    }

    public function setColumn($name)
    {
        $this->column = "`$name`";
    }

    public function setColumnWithPreffix($name)
    {
        $this->column = $name;
    }

    public function setKeyName($name)
    {
        $this->key = "`$name`";
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getPrimaryKeys()
    {
        return $this->primary_keys;
    }

    public function setPkMode($mode)
    {
        $this->pk_mode = $mode;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setTable($table)
    {
        $this->table = $table;
    }
}
