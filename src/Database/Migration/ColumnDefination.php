<?php

namespace Boiler\Core\Database\Migration;

use Boiler\Core\Database\DataTypes;
use Boiler\Core\Database\Schema;

class ColumnDefination extends DataTypes
{


    public function column($name)
    {

        $this->column = "`$name`";
        $this->key = "$name";
        return $this;
    }

    /**
     * Declaring the column name
     * 
     * @param $name 
     * @deprecated 
     * 
     * @return $this
     */
    public function field($name)
    {

        $this->column = "`$name`";
        $this->key = "$name";
        return $this;
    }

    public function after($column)
    {
        return $this->query = concat([$this->trimmer($this->query), "AFTER",  "`$column`"]);
    }

    public function addColumn($name)
    {

        $mode = 'ADD';

        if (!empty($this->query)) {
            if (preg_match('/ADD/', $this->query)) {
                $this->query = $this->trimmer($this->query);
                $mode = ', ADD';
            }
        }

        $this->column = concat([$mode, "`$name`"]);

        $this->key = "$name";
        return $this;
    }

    public function changeColumnName($current_name, $new_name)
    {

        $this->column = concat(["CHANGE", "`$current_name`", "`$new_name`"]);
        $this->key = "$new_name";
        return $this;
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

        $this->column("created_date")->timestamp()->default("CURRENT_TIMESTAMP()");
        $this->column("updated_date")->timestamp()->default("CURRENT_TIMESTAMP()");
    }
}
