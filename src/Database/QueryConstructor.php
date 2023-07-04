<?php

namespace Boiler\Core\Database;

class QueryConstructor
{

    protected $unique_column_name = "id";

    protected $driver;

    protected $builder;

    protected $schemaManager;

    protected $searchIndex = 0;

    protected array $parameters = [];

    protected $fields;

    protected static $builderStatic;

    protected $selectedColumns = '*';

    protected $hasFrom = false;

    public function __construct(protected Connection $conn)
    {
        $this->driver = $this->conn->getDriver();
        $this->builder = $conn->getConnection()->createQueryBuilder();
    }

    public function resetQueryBuilder()
    {
        $this->selectedColumns = '*';
        $this->hasFrom = false;
        $this->builder = $this->conn->getConnection()->createQueryBuilder();
    }

    protected function fromQuery($table) {

        if(!$this->hasFrom) {
            $this->builder->from($table, $table);
            $this->hasFrom = true;
        }
    }

    public function allQuery($table)
    {
        $this->builder->select($this->selectedColumns);
        $this->fromQuery($table);
    }

    public function selectQuery(array|string $fields, string $table)
    {
        $this->selectedColumns = is_array($fields) ? implode(',', $fields) : $fields;
        $this->builder->select($this->selectedColumns);
        
        $this->fromQuery($table);

        return $this;
    }


    public function insertQuery(array $data, string $table)
    {

        $values = [];
        foreach ($data as $column => $value) {
            $values[$column] = '?';
            array_push($this->parameters, $value);
        }
        $this->builder->insert($table)->values($values);

        foreach ($this->parameters as $index => $value) {
            $this->builder->setParameter($index, $value);
        }


        return $this;
    }


    public function updateQuery($data, $table)
    {
        $this->builder->update($table);

        foreach ($data as $column => $val) {
            $this->builder->set($column, '?');
            array_push($this->parameters, $val);
        }
    }

    protected function deleteQuery($data = null, $table = null)
    {

        $this->builder->delete($table);

        $unique_column_name = $this->unique_column_name;

        if ($data !== null) {
            foreach ($data as $column => $value) {
                if (count($this->parameters) > 0) {
                    $this->builder->andWhere($column . " = ?");
                } else {
                    $this->builder->where($column . " = ?");
                }
                array_push($this->parameters, $value);
            }
        } else {
            if (strpos(strtolower($this->getSql()), "where") === false) {
                if (isset($this->$unique_column_name)) {
                    $this->builder->where($unique_column_name . ' = ?');
                    array_push($this->parameters, $this->$unique_column_name);
                }
            }
        }
    }

    protected function whereQuery(array|string $key, $value = null, $operation = null)
    {

        $op = $operation ? $operation : '=';

        if (is_array($key)) {

            $index = 0;
            foreach ($key as $column => $val) {
                if (count($this->parameters) > 0) {
                    $this->builder->andWhere($column . " $op ?");
                } else {
                    $this->builder->where($column . " $op ?");
                }
                array_push($this->parameters, $val);

                $index++;
            }
        } else {

            if (is_string($key)) {

                $use_value = true;

                if (preg_match('/\s/',$key) && $value == null){
                    $use_value = false;
                }

                if (count($this->parameters) > 0) {
                    $use_value ? $this->builder->andWhere($key . " $op ?") : $this->builder->andWhere($key);
                } else {
                    $use_value ? $this->builder->where($key . " $op ?") : $this->builder->where($key);
                }

                if ($use_value) {
                    array_push($this->parameters, $value);
                }
            }
        }
    }

    protected function orWhereQuery($key, $value = null)
    {
        $use_value = true;

        if (preg_match('/\s/',$key) && $value == null){
            $use_value = false;
        }

        if ($use_value) {
            $this->builder->orWhere("`$key` = ?");
            return array_push($this->parameters, $value);
        } else {
            $this->builder->orWhere($key);
        }
    }

    protected function searchQuery($key, $value, $operation)
    {

        if (is_array($key)) {

            foreach ($key as $column => $val) {

                $val = $operation[0] . $val . $operation[1];
                $search = "`$column` LIKE ?";

                if (count($this->parameters) > 0) {
                    $this->builder->andWhere($search);
                } else {
                    $this->builder->where($search);
                }

                array_push($this->parameters, $val);
            }
        } else {

            if (is_string($key)) {

                $value = $operation[0] . $value . $operation[1];
                if (count($this->parameters) > 0) {
                    $this->builder->andWhere("`$key` LIKE ?");
                } else {
                    $this->builder->where("`$key` LIKE ?");
                }

                array_push($this->parameters, $value);
            }
        }
    }

    protected function orSearchQuery($key, $value, $operation)
    {

        if (is_array($key)) {

            foreach ($key as $column => $val) {

                $val = $operation[0] . $val . $operation[1];
                $search = "`$column` LIKE ?";

                $this->builder->orWhere($search);
                array_push($this->parameters, $val);
            }
        } else {

            if (is_string($key)) {

                $value = $operation[0] . $value . $operation[1];

                $this->builder->orWhere("`$key` LIKE ?");
                array_push($this->parameters, $value);
            }
        }
    }

    public function orderQuery(string $key, string $order = "ASC", string|array $limit = null)
    {

        $this->builder->orderBy($key, $order);

        if (!is_null($limit)) {
            $this->setLimit($limit);
        }
    }

    public function addOrderBy($column, $mode = 'ASC')
    {
        $this->builder->addOrderBy($column, $mode);

        return $this;
    }

    public function setLimit(int|string|array $limit)
    {

        if (is_string($limit) && stripos($limit, ',')) {
            list($first, $max) = explode(',', $limit);
            $this->builder->setFirstResult($first)->setMaxResults($max);

            return;
        }

        if (is_array($limit)) {
            if (isset($limit[0]) && isset($limit[1])) {
                $this->builder->setFirstResult($limit[0])->setMaxResults($limit[1]);
            }

            return;
        }

        if (is_integer($limit)) {
            $this->builder->setMaxResults($limit);

            return;
        }
    }

    public function groupQuery($option)
    {
        $this->builder->groupBy($option);

        if (!in_array($this->driver, ["sqlite", "pdo_sqlite"])) {
            $this->conn->getConnection()->executeQuery('SET sql_mode=(SELECT REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", ""))');
        }
    }

    public function sumQuery($column, $table)
    {
        $this->builder->select("SUM($column) as $column");
        $this->fromQuery($table);
    }

    public function getSql()
    {
        return $this->builder->getSQL();
    }

    protected function dataFormatChecker($key, $value)
    {
        $data = $key;

        if (gettype($key) == "string") {
            $data = array($key => $value);
        }

        return $data;
    }

    protected function fieldFormatChecker($fields)
    {

        is_null($fields) ? $fields = "*" : null;
        return $this->fields = $fields;
    }

    protected function resultTypeChecker($result)
    {
        return gettype($result);
    }
}
