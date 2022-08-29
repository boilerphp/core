<?php

namespace Boiler\Core\Database;


class QueryConstructor
{

    protected $driver;

    protected $builder;

    protected $schemaManager;

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
        $this->columns = "";
        $this->builder->delete($table);

        if($data !== null) {
            foreach ($data as $column => $value) {
                $this->builder->where($column." = ?");
                array_push($this->parameters, $value);
            }
        }

        else {
            if(!strpos(strtolower($this->getSql()), "where")) {
                $id = $this->id ?? null;
                if($id) {
                    $this->builder->where('id = ?');
                    array_push($this->parameters, $id);
                }
            }
        }

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

    protected function searchQuery($key, $value, $operation)
    {

        if (is_array($key)) {

            $index = 0;
            foreach ($key as $column => $val) {

                $val = $operation[0] . $val . $operation[1];
                $search = "`$column` LIKE '$val'";
                $this->builder->where($search);

                $index++;
            }
        } else {

            if (is_string($key)) {

                if (is_null($value)) {

                    $this->builder->where($key);
                } else {

                    $value = $operation[0] . $value . $operation[1];
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

            if (is_string($limit) && stripos($limit, ',')) {
                list($first, $max) = explode(',', $limit);
                $this->builder->setFirstResult($first)->setMaxResults($max);
            }
        }
    }

    public function groupQuery($option)
    {
        $this->builder->groupBy($option);

        if(!in_array($this->driver, ["sqlite", "pdo_sqlite"])) {
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
