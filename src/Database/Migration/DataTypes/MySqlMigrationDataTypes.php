<?php

namespace Boiler\Core\Database\Migration\DataTypes;


class MySqlMigrationDataTypes extends AbstractMigrationDataTypes implements DataTypesInterface
{

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
     * Set column datatype to big increments
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function bigIncrements()
    {
        if ($this->pk_mode) {
            $this->primary_keys .= " $this->key,";
        }

        $this->query .= " $this->column BIGINT(20) AUTO_INCREMENT,";
        return $this;
    }

    /**
     * Set column datatype to integer datatype
     * with auto increment value.
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function increments()
    {
        if ($this->pk_mode) {
            $this->primary_keys .= " $this->key,";
        }

        $this->query .= " $this->column INT(16) AUTO_INCREMENT,";
        return $this;
    }

    /**
     * Creates a column with name id and sets datatype to a 
     * big integer datatype with auto increment value.
     * 
     * @param $name - Default value 'id'
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function id($name = "id")
    {
        $this->column = $name;
        $this->primary_keys .= " $this->column,";
        $this->query .= " $this->column BIGINT(20) AUTO_INCREMENT,";
        return $this;
    }


    /**
     * Creates a unique column with name id and sets datatype to
     * big integer datatype with key and auto increment value. 
     * 
     * @param $name - Default value 'id'
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function uniqueId($name = "id")
    {
        $this->column = $name;
        $this->primary_keys .= " $this->column,";
        $this->query .= " $this->column BIGINT(20) NOT NULL UNIQUE,";
        return $this;
    }


    /**
     * Creates a unique column with varchar datatype. 
     * 
     * @param $length - Default value '100'
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function stringId($length = 100)
    {
        $this->primary_keys .= " $this->column,";
        $this->query .= " $this->column VARCHAR(" . (string) $length . ") UNIQUE,";
        return $this;
    }


    /**
     * Set column datatype to integer
     * 
     * @param $length - Default value '9'
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function integer($length = 9)
    {
        $this->query .= " $this->column INT(" . (string) $length . "),";
        return $this;
    }


    /**
     * Set column datatype to varchar
     * 
     * @param $length - Default value '100'
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function string($length = 100)
    {
        $this->query .= " $this->column VARCHAR(" . (string) $length . "),";
        return $this;
    }


    /**
     * Set column datatype to text
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function text()
    {
        $this->query .= " $this->column TEXT,";
        return $this;
    }


    /**
     * Set column datatype to longtext
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function longtext()
    {
        $this->query .= " $this->column LONGTEXT,";
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
    public function float($length = 8, $decimal = 2)
    {
        $this->query .= " $this->column FLOAT(" . (string) $length . ", " . (string) $decimal . "),";
        return $this;
    }

    /**
     * Set column datatype to double datatype
     * 
     * @param $length
     * @param $decimal 
     * 
     * @return self
     */
    public function double($length = 8, $decimal = 2)
    {
        $this->query .= " $this->column DOUBLE(" . (string) $length . ", " . (string) $decimal . "),";
        return $this;
    }

    /**
     * Set column datatype to double precision datatype
     * 
     * @return self
     */
    public function doublePrecision()
    {
        $this->query .= " $this->column DOUBLE PRECISION,";
        return $this;
    }

    /**
     * Set column datatype to decimal datatype
     * 
     * @param $length
     * @param $decimal 
     * 
     * @return self
     */
    public function decimal($length = 8, $decimal = 2) {

        $this->query .= " $this->column DECIMAL(" . (string) $length . ", " . (string) $decimal . "),";
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

    /**
     * Define column as primary key
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function primary($column = "")
    {
        if ($column != "") {
            $this->primary_keys .= " $column,";
        } else {
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
     * @return Boiler\Core\Database\DataTypes
     */
    public function nullable($state = true)
    {

        $this->query = trimmer($this->query, ",");
        $this->query .= ($state) ? " DEFAULT NULL," : " NOT NULL";
        return $this;
    }

    /**
     * Set column datatype to time
     * 
     * @return Boiler\Core\Database\DataTypes
     */
    public function time()
    {
        $this->query .= " $this->column TIME,";
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
     * Set column datatype to datetime
     * 
     * @return Boiler\Core\Database\DataTypes
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
        $this->foreignKeys .= " ADD CONSTRAINT `$const` FOREIGN KEY (`$this->key`) REFERENCES `$table` (`$reference`) ,";

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

    public function after($column)
    {
        $this->query = trimmer($this->query, ","). " AFTER `$column`";
    }
}
