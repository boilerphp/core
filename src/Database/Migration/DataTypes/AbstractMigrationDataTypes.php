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


    protected $table;


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
    abstract public function bigInteger($length = 20);

    /**
     * Set column datatype to unsigned big integer
     * 
     * @param $length 
     * 
     * @return self
     */
    abstract public function unsignedBigInteger($length = 20);

    /**
     * Set column datatype to boolean
     * 
     * @return self
     */
    abstract public function boolean();

    /**
     * Set column datatype to date
     * 
     * @return self
     */
    abstract public function date();

    /**
     * Set column datatype to floating datatype
     * 
     * @param $length
     * @param $decimal 
     * 
     * @return self
     */
    abstract public function float($length = 8, $decimal = 2);

    /**
     * Set column datatype to decimal datatype
     * 
     * @param $length
     * @param $decimal 
     * 
     * @return self
     */
    abstract public function decimal($length = 8, $decimal = 2);

    /**
     * Set column datatype to double datatype
     * 
     * 
     * @return self
     */
    abstract public function double();

    /**
     * Set column datatype to double precision datatype
     * 
     * 
     * @return self
     */
    abstract public function doublePrecision();


    /**
     * Define a column as a foreign key column
     * and set the relationship keys.
     * 
     * @param $table - name of the foreign table
     * @param $reference - relating column of the foreign table
     * 
     * @return Boiler\Core\Database\ColumnDefination
     */
    abstract public function foreign($table, $reference = "id");


    abstract public function foreignKeyProccessor($table);


    abstract public function after($column);


    public function cascade()
    {
        $this->foreignKeys = trimmer($this->foreignKeys, ",");
        $this->foreignKeys .= "ON DELETE CASCADE ,";
        return $this;
    }

    /**
     * Set column default value
     * 
     * @return self
     */
    public function default($value)
    {

        $this->query = trimmer($this->query, ",");
        $this->query = trimmer($this->query, "NOT NULL");
        $this->query .= " DEFAULT {$value},";

        return $this;
    }

    public function setColumn($name)
    {
        $this->column = "`$name`";
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function setColumnWithPreffix($name)
    {
        $this->column = $name;
    }

    public function setKeyName($name)
    {
        $this->key = $name;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return trimmer($this->query, ",");
    }

    public function getPrimaryKeys()
    {
        return trimmer($this->primary_keys, ",");
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

    public function trimQuery() {
        $this->query = trimmer($this->query, " ,");
    }
}
