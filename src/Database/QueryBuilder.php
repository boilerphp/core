<?php

namespace Boiler\Core\Database;



class QueryBuilder extends DataTypes
{

	/**
	 * mapped columns by query builder
	 *
	 * @var string
	 *
	 */
	protected $columns = "";

	/**
	 * mapped params by query builder
	 *
	 * @var string
	 *
	 */
	protected $params = "";

	/**
	 * order operations query
	 * formated by query builder
	 *
	 * @var string
	 *
	 */
	protected $orderQuery = "";


	/**
	 * where operations query
	 * formated by query builder
	 *
	 * @var string
	 *
	 */
	protected $groupQuery = "";


	/**
	 * where operations query 
	 * formated by query builder
	 *
	 * @var string
	 *
	 */
	protected $whereQuery = "";


	/**
	 * Query data map by query builder
	 *
	 * @var array
	 *
	 */
	protected $whereData = [];


	/**
	 * Formated data for query builder
	 *
	 * @var array
	 *
	 */
	protected $data = [];


	protected function cleanQueryStrings()
	{
		isset($this->columns) ? $this->columns = trim($this->columns, ", ") : null;
		isset($this->params) ? $this->params = trim($this->params, ", ") : null;
	}

	protected function allQuery()
	{
		if ($this->queryString == "") {
			$this->queryString = "SELECT * FROM $this->table ";
		}
	}

	protected function insertQuery($data)
	{
		$this->columns = "";
		$this->params = "";

		foreach ($data as $column => $value) {
			$this->columns .= "`$column`, ";
			$this->params .= ":$column, ";
		}

		$this->cleanQueryStrings();

		$this->queryString = "INSERT INTO $this->table ($this->columns) VALUES($this->params)";
		return $this->queryString;
	}

	protected function sumQuery($column)
	{

		$this->queryString = "SELECT SUM($column) as $column FROM $this->table ";
		return $this->queryString();
	}

	protected function selectQuery($fields = "*")
	{

		$this->queryString = "SELECT $fields FROM $this->table ";
	}

	protected function updateQuery($data)
	{
		$this->columns = "";
		foreach ($data as $column => $value) {
			$this->columns .= "`$column` = :$column, ";
		}

		$this->cleanQueryStrings();

		$this->queryString = "UPDATE $this->table SET $this->columns ";
		$this->whereData = array_merge($this->whereData, $data);
		return $this->queryString;
	}


	protected function deleteQuery($data)
	{
		$this->columns = "";

		foreach ($data as $column => $value) {
			$this->columns .= "`$column` = :$column, ";
		}

		$this->cleanQueryStrings();

		$this->queryString = "SET FOREIGN_KEY_CHECKS = 0; DELETE FROM $this->table WHERE $this->columns";
		$this->whereData = array_merge($this->whereData, $data);
		return $this->queryString;
	}

	protected function searchQuery($key, $value, $operation)
	{

		if ($this->whereQuery == "") {
			$this->whereQuery = " WHERE ";
		}

		if (is_array($key)) {

			$search = " ( ";
			foreach ($key as $column => $val) {
				$val = $operation[0] . $val . $operation[1];
				$search .= " `$column` LIKE '$val' OR ";
			}

			$search =  trim($search, "OR ");
			$search .= " ) ";
			$this->whereQuery .= $search . " AND ";
		} else if (!is_array($key) && $value != null) {
			$value = $operation[0] . $value . $operation[1];

			$this->whereQuery .= " `$key` LIKE '$value' AND ";
		}
	}

	protected function whereQuery($key, $value, $operation = null)
	{
		if ($this->whereQuery == "") {
			$this->whereQuery = " WHERE ";
		}

		if (is_array($key)) {
			$index = 0;
			foreach ($key as $column => $val) {
				if ($operation != null) {
					if (is_array($operation)) {
						$op = $operation[$index];
						$this->whereQuery .= " `$column` $op '$val' AND ";
					} else {
						$this->whereQuery .= " `$column` $operation '$val' AND ";
					}
				} else {
					$this->whereQuery .= " `$column` = :$column AND ";
					$this->whereData = array_merge($this->whereData, $key);
				}

				$index++;
			}
		} else if (!is_array($key)) {
			if ($operation != null) {

				$this->whereQuery .= "`$key` $operation '$value' AND ";
			} else {

				$this->whereQuery .= "`$key` = :$key AND ";
				$this->whereData = array_merge($this->whereData, array($key => $value));
			}
		}
	}

	protected function orWhereQuery($key, $value, $operation = null)
	{
		$this->whereQuery = trim($this->whereQuery, "AND ") . " OR ";

		if (is_array($key)) {
			$index = 0;
			foreach ($key as $column => $val) {
				if ($operation != null) {
					if (is_array($operation)) {
						$op = $operation[$index];
						$this->whereQuery .= " `$column` $op '$val' OR ";
					} else {
						$this->whereQuery .= " `$column` $operation '$val' OR ";
					}
				} else {
					$this->whereQuery .= " `$column` = :$column OR ";
					$this->whereData = array_merge($this->whereData, $key);
				}

				$index++;
			}
		} else if (!is_array($key)) {
			if ($operation != null) {

				$this->whereQuery .= "`$key` $operation '$value' OR ";
			} else {

				$this->whereQuery .= "`$key` = :$key OR ";
				$this->whereData = array_merge($this->whereData, array($key => $value));
			}
		}
	}

	protected function groupQuery($column)
	{
		$this->groupQuery = " GROUP BY `$column`";
	}

	protected function orderQuery($key, $order, $limit)
	{
		$this->orderQuery = " ORDER BY `$key` $order";
		if ($limit != null) {
			$this->orderQuery .= " LIMIT " . $limit;
		}
	}

	public function limits($start, $end)
	{
		$limits = $start . ", " . $end;
		$this->orderQuery .= " LIMIT " . $limits;
		return $this;
	}

	protected function queryString()
	{
		if (!empty($this->queryString)) {

			if (!empty($this->whereQuery)) {
				$this->queryString .= trim($this->whereQuery, "AND ");
				$this->queryString = trim($this->queryString, "OR ");
			}

			if (!empty($this->groupQuery)) {
				$this->queryString .= $this->groupQuery;
			}

			if (!empty($this->orderQuery)) {
				$this->queryString .= $this->orderQuery;
			}

			return $this->queryString;
		}

		return null;
	}

	protected function dataFormatChecker($data, $value)
	{

		if (gettype($data) == "string") {
			if (!is_null($value)) {
				return $this->data = array($data => $value);
			} else {
				// $this->valueIsNullException();
			}
		}

		return $this->data = $data;
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


	protected function dropDatabaseTableQuery($table)
	{
		return "SET FOREIGN_KEY_CHECKS = 1; DROP TABLE IF EXISTS `$table`; SET FOREIGN_KEY_CHECKS = 0; DROP TABLE IF EXISTS `$table`;";
	}
}
