<?php

namespace Boiler\Core\Database;


class QueryConstructor
{

    protected $builder;

    protected $schemaManager;

    protected array $parameters = [];

    public function __construct(protected Connection $conn)
    {
        $this->builder = $conn->getConnection()->createQueryBuilder();
    }

    public function resetBuilder() {
        $this->builder = $this->conn->getConnection()->createQueryBuilder();
    }

    public function allQuery($table) {

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


    public function updateQuery($data, $table) {

        $this->builder->update($table);
        foreach ($data as $column => $val) {
            $this->builder->set($column, '?');
            array_push($this->parameters, $val);
        }
    }

    protected function deleteQuery($data)
	{
		$this->columns = "";
        // $this->builder->delete($table);
		
	}

    public function whereQuery(array|string $key,  array|string|null $value = null)
    {

        if (is_array($key)) {

            $index = 0;
            foreach ($key as $column => $val) {
                $this->builder->where($column . ' = ?');
                array_push($this->parameters, $val);

                $index++;
            }
        } else {

            if (is_string($key)) {

                if (is_null($value)) {

                    $this->builder->where($key);
                } else {
                    $this->builder->where($key . ' = ?');
                    array_push($this->parameters, $value);
                }
            }
        }
    }

    public function orderQuery(string $key, string $order = "ASC", string|array $limit = null)
    {

        $this->builder->orderBy($key, $order);

        if(!is_null($limit)) {

            if(is_string($limit) && stripos($limit, ',')) {
                list($first, $max) = explode(',', $limit);
                $this->builder->setFirstResult($first)->setMaxResults($max);
            }
        }
    }

    public function groupQuery($option) {
        $this->builder->groupBy($option);
        return $this;
    }

    public function sumQuery($column, $table) {
        $this->builder->select("SUM($column) as $column")->from($table);
    }

    public function getSql()
    {
        return $this->builder->getSQL();
    }

    protected function dataFormatChecker($key, $value)
	{

        $data = null;

		if (gettype($key) == "string") {
			if (!is_null($value)) {
				return $data = array($key => $value);
			}
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
