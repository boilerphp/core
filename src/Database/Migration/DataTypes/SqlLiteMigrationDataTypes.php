<?php 

namespace Boiler\Core\Database\Migration\DataTypes;

class SqlLiteMigrationDataTypes extends AbstractMigrationDataTypes implements DataTypesInterface {

    /**
     * Set column datatype to big increments
     * 
     * @return self
     */
    public function bigIncrements() 
    {
        if($this->pk_mode) {
            $this->primary_keys .= " $this->key,";
        }

        $this->query .= " $this->column INTEGER AUTOINCREMENT,";
        return $this;
    }


    /**
     * Set column datatype to integer datatype
     * with auto increment value.
     * 
     * @return self
     */
    public function increments() 
    {
        if($this->pk_mode) {
            $this->primary_keys .= " $this->key,";
        }

        $this->query .= " $this->column INTEGER AUTOINCREMENT,";
        return $this;
    }


    /**
     * Creates a column with name id and sets datatype to a 
     * big integer datatype with auto increment value.
     * 
     * @param $name - Default value 'id'
     * 
     * @return self
     */
    public function id($name = "id")
    {
        $this->column = "`$name`";
        $this->query .= " $this->column INTEGER PRIMARY KEY AUTOINCREMENT,";
    }


    /**
     * Creates a unique column with name id and sets datatype to
     * big integer datatype with key and auto increment value. 
     * 
     * @param $name - Default value `id`
     * 
     * @return self
     */
    public function uniqueId($name = "id")
    {
        $this->column = "`$name`";
        $this->primary_keys .= " $this->column,";
        $this->query .= " $this->column INT NOT NULL UNIQUE,";
        return $this;
    }


    /**
     * Creates a unique column with varchar datatype. 
     * 
     * @param $length - Default value 100
     * 
     * @return self
     */
    public function stringId($length = 100)
    {
        $this->primary_keys .= " `$this->column`,";
        $this->query .= " $this->column TEXT NOT NULL UNIQUE,";
        return $this;
    }


    /**
     * Set column datatype to integer
     * 
     * @param $length - Default value 9
     * 
     * @return self
     */
    public function integer($length = 9) 
    {
        $this->query .= " $this->column INT($length),";
        return $this;
    }


    /**
     * Set column datatype to varchar
     * 
     * @param $length - Default value '100'
     * 
     * @return self
     */
    public function string($length = 100) 
    {
        $this->query .= " $this->column VARCHAR($length),";
        return $this;
    }


    /**
     * Set column datatype to text
     * 
     * @return self
     */
    public function text() 
    {
        $this->query .= " $this->column TEXT,";
        return $this;
    }


    /**
     * Set column datatype to longtext
     * 
     * @return self
     */
    public function longtext() 
    {
        $this->query .= " $this->column TEXT,";
        return $this;
    }

    /**
     * Define column as primary key
     * 
     * @return self
     */
    public function primary($column = "")
    {
        if($column != "") {
            $this->primary_keys .= " $column,";
        } else 
        {
            $this->primary_keys .= " $this->key,";
        }
        return $this;
    }

    /**
     * Define default state of a column
     * If set 'true' value will be NULL
     * and if set 'false' value will be NOT NULL 
     * 
     * @param $state - Default value 'true'
     * 
     * @return self
     */
    public function nullable($state = true) 
    {

        $this->query = trimmer($this->query, ",");
        $this->query .= ($state) ? " DEFAULT NULL," : "NOT NULL";
        return $this;
    }

    /**
     * Set column datatype to time
     * 
     * @return self
     */
    public function time() 
    {
        $this->query .= " $this->column TIME,";
    }


    /**
     * Set column datatype to datetime
     * 
     * @return self
     */
    public function timestamp() 
    {
        $this->query .= " $this->column DATETIME,";
        return $this;
    }

    public function unique() 
    {
        $this->query = trimmer($this->query, ",");
        $this->query .= " UNIQUE,";
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
        $reference = is_null($reference) ? $this->key : $reference;

        $const = $table . "_" . $this->table . "_" . $this->key . "_fk";
        $this->foreignKeys .= "FOREIGN KEY (`$this->key`) REFERENCES `$table` (`$reference`) ,";

        return $this;
    }

    public function foreignKeyProccessor($table)
    {
        if ($this->foreignKeys != "") {
            $query = trimmer($this->foreignKeys, ",");
            return $query;
        }
    }
}