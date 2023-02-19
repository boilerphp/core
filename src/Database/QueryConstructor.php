<?php

namespace Boiler\Core\Database;

use Exception;

class QueryConstructor
{

    protected $driver;

    protected $builder;

    protected $schemaManager;

    protected $searchIndex = 0;

    protected array $parameters = [];

    public function __construct(protected Connection $conn)
    {
        $this->driver = $this->conn->getDriver();
        $this->builder = $conn->getConnection()->createQueryBuilder();
    }

    public function resetBuilder()
    {
        $this->builder = $this->conn->getConnection()->createQueryBuilder();
    }

    public function allQuery($table)
    {

        $this->builder->select("*")->from($table);
    }

    public function selectQuery(array|string $fields, string $table)
    {

        $this->builder->select(
            is_array($fields) ? implode(',', $fields) : $fields
        )->from($table);

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

        if ($data !== null) {
            foreach ($data as $column => $value) {
                $this->builder->where($column . " = ?");
                array_push($this->parameters, $value);
            }
        } else {
            if (strpos(strtolower($this->getSql()), "where") === false) {
                if (isset($this->id)) {
                    $this->builder->where('id = ?');
                    array_push($this->parameters, $this->id);
                }
            }
        }
    }

    public function whereQuery(array|string $key,  array|string $value = null, string $operation = null)
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

                if (count($this->parameters) > 0) {
                    $this->builder->andWhere($key . " $op ?");
                } else {
                    $this->builder->where($key . " $op ?");
                }
                array_push($this->parameters, $value);
            }
        }
    }

    protected function searchQuery($key, $value, $operation)
    {

        if (is_array($key)) {

            foreach ($key as $column => $val) {

                $val = $operation[0] . $val . $operation[1];
                $search = "`$column` LIKE '$val'";

                if ($this->searchIndex > 0) {
                    $this->builder->andWhere($search);
                } else {
                    $this->builder->where($search);
                }

                $this->searchIndex++;
            }
        } else {

            if (is_string($key)) {

                $value = $operation[0] . $value . $operation[1];
                if ($this->searchIndex > 0) {
                    $this->builder->andWhere("`$key` LIKE '$value'");
                } else {
                    $this->builder->where("`$key` LIKE '$value'");
                }
            }
        }
    }

    protected function orWhereQuery($key, $value, $operation = null)
    {

        if (is_array($key)) {
            $index = 0;
            foreach ($key as $column => $val) {
                if ($operation != null) {
                    if (is_array($operation)) {
                        $op = $operation[$index];
                        $this->builder->orWhere("`$column` $op '$val'");
                    } else {
                        $this->builder->orWhere("`$column` $operation '$val'");
                    }
                } else {
                    $this->builder->orWhere("`$column` = ?");
                    array_push($this->parameters, $val);
                }

                $index++;
            }
        } else if (!is_array($key)) {
            if ($operation != null) {

                $this->builder->orWhere("`$key` $operation '$value'");
            } else {

                $this->builder->orWhere("`$key` = ?");
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

    public function setLimit(string|array $limit)
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
        $this->builder->select("SUM($column) as $column")->from($table);
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
