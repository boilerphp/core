<?php

namespace Boiler\Core\Database;

use ReflectionClass;
use ReflectionMethod;
use Boiler\Core\Configs\GlobalConfig;

class Schema extends QueryConstructor
{

    /**
     * Query formated by query builder
     *
     * @var string
     *
     */
    protected $result_data_format = "object";


    /**
     * Query formated by query builder
     *
     * @var string
     *
     */
    protected $queryString = "";


    /**
     * Database to be targeted
     *
     * @var string
     *
     */
    protected $database;


    /**
     * Database Schema table name
     *
     * @var string
     *
     */
    protected $table;


    /**
     * Database query result 
     *
     * @var array|object|mixed
     *
     */
    protected $result;


    /**
     * Query selection fields 
     *
     * @var string
     *
     */
    protected $fields;


    /**
     * Database connection 
     *
     * @var Boiler\Core\Database\Connection
     *
     */
    protected $connection;



    protected $relations = true;



    protected $countOnly = false;



    public function __construct(string $database = null)
    {
        $this->database = $database;
        $this->connection =  $this->getSocket();
        $this->driver = $this->connection->getDriver();

        parent::__construct($this->connection);
    }

    public function getSocket()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if ($this->database !== null && $this->database !== 'default') {

            return GlobalConfig::getTargetConnection($this->database);
        } else {

            if (!GlobalConfig::$IS_CONNECTED) {

                GlobalConfig::setAppConnetion();
            }

            return GlobalConfig::getAppConnection();
        }
    }


    public function connection()
    {
        return $this->getSocket()->getConnection();
    }


    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }


    public static function queryBuilder()
    {
        $instance = new static;
        $instance->setTableName();
        $table = $instance->getTableName();
        return $instance->connection()->createQueryBuilder()->from($table, $table);
    }


    public function attachClass($all = false, $fields = null)
    {
        $this->relations = false;

        if ($all == true) {
            $this->allQuery($this->table);
            $data = $this->fetch(true);

            return is_null($data)
                ? $data
                : (!is_array($data) ? array($data) : $data);
        } else {

            if ($this->fieldFormatChecker($fields)) {
                $this->selectQuery($this->fields, $this->table);
                return $this->fetch(true);
            }
        }
    }


    protected function positionCollection($position, $key, $value)
    {

        if (!is_null($key) && !is_null($value)) {
            $this->where($key, $value);
        }

        if ($position === 'first') {
            $this->orderBy($this->unique_column_name, 'ASC')->limit(1);
        } else if ($position === 'last') {
            $this->orderBy($this->unique_column_name, 'DESC')->limit(1);
        }

        $result = $this->get();
        return $result;
    }


    public function all()
    {
        $this->allQuery($this->table);
        $data = $this->fetch();

        if (is_null($data)) {
            return [];
        }

        if (!is_array($data)) {
            return array($data);
        }

        return $data;
    }


    protected function bootRelations($class)
    {
        $class_name = get_class($class);

        $clReflection = new ReflectionClass($class_name);

        foreach ($clReflection->getMethods() as $clMethod) {

            if ($clMethod->class == $class_name) {

                $method = $clMethod->name;

                if ($method != '__construct') {
                    $mReflection = new ReflectionMethod($class_name, $method);
                    $params = $mReflection->getParameters();

                    if (count($params) == 0) {
                        $result = $class->{$method}();
                        $instance = $result;
                        if (is_array($result)) {
                            if (isset($result[0])) {
                                $instance = $result[0];
                            }
                        }

                        if ($instance instanceof Model) {
                            $class->{$method} = $result;
                        }
                    }
                }
            }
        }
    }


    public function count()
    {
        $this->countOnly = true;
        $this->relations = false;

        $count = $this->select($this->selectedColumns)->fetch();

        $this->countOnly = false;
        return $count;
    }


    public function clearInitalQuery()
    {
        $this->parameters = [];
        $this->searchIndex = 0;
        $this->resetQueryBuilder();
    }


    public function first($key = null, $value = null)
    {
        return $this->positionCollection('first', $key, $value);
    }


    public function last($key = null, $value = null)
    {
        return $this->positionCollection('last', $key, $value);
    }


    public function find($key, $value = null)
    {
        if ($value == null) {
            $this->result = $this->where($this->unique_column_name, $key)->get();
        } else {
            $this->result = $this->where($key, $value)->get();
        }

        if ($this->result !== null) {
            return $this->result;
        }

        return null;
    }


    public function groupBy($column)
    {
        $this->groupQuery($column);
        return $this;
    }


    public function orderBy($key, $order = "ASC", $limit = null)
    {
        $this->orderQuery($key, $order, $limit);
        return $this;
    }

    public function limit($value)
    {
        $this->setLimit($value);
    }

    public function sum($column)
    {
        $this->sumQuery($column, $this->table);

        $result = $this->fetch();
        return $result->$column ?? 0;
    }


    public function paginate($number, $page = 1)
    {

        $start = (($page - 1) * $number);
        $end = ($page * $number);

        $from = $page;
        $to = $number;
        if ($page > 1) {
            $from = ($start + 1);
            $to = $end;
        }

        $limits = $start . ", " . $number;

        $clone = clone $this;
        $total_result = $clone->count();

        $this->setLimit($limits);
        $result = $this->fetch();

        if (!is_null($result) && !is_array($result)) {
            $result = array($result);
        } else if (!is_null($result)) {
            $result = $result;
        }

        if ($total_result > $number) {
            $total_pages = floor($total_result / $number);

            if (($total_result % $number) > 0) {
                $total_pages += 1;
            }
        } else {
            $total_pages = 1;
        }

        if ($to > $total_result) {
            $to = $total_result;
        }

        $data = [
            "page" => $page,
            "start_at" => $from,
            "end_at" => $to,
            "total" => $total_result,
            "pages" => $total_pages,
            "per_page" => $number
        ];

        $response = json_encode($data);
        setEnv("_pagination", $response);

        return $result;
    }

    public function create(array $data, callable|null $callback = null)
    {
        // if(!array_key_exists('created_date', $data)) {
        //     $data['created_date'] = time();
        // }

        $instance = $this->insert($data);
        if ($callback !== null) {
            return $callback($instance);
        }

        return $instance;
    }

    public function createAll(array $list = [], callable|null $callback = null)
    {
        if (count($list)) {

            $result = [];
            $error = false;
            $index = 0;

            foreach ($list as $instance) {

                if (is_array($instance)) {
                    $new_instance = $this->create($instance);
                    if ($new_instance) {
                        array_push($result, $new_instance);
                    } else {
                        $error = [
                            "message" => "Unable to create instance at index: {$index}"
                        ];
                        break;
                    }
                } else {
                    $error = [
                        "message" => "Found an invalid instance at index {$index} of data list"
                    ];
                    break;
                }

                $index++;
            }

            if ($callback !== null) {
                return $callback($result, $error);
            }

            return $result;
        }

        return null;
    }

    public function insert(array $data)
    {
        if ($data) {
            if ($this->insertQuery($data, $this->table)) {

                $statement = $this->builder->executeQuery();
                if ($statement) {
                    return $this->last_inserted_row();
                }
            }
        }

        return null;
    }

    protected function last_inserted_row()
    {
        if ($this->driver === "sqlite" || $this->driver === "pdo_sqlite") {
            $last_inserted = $this->query("SELECT * FROM $this->table WHERE {$this->unique_column_name} = last_insert_rowid()");
        } else {
            $last_inserted = $this->query("SELECT * FROM $this->table WHERE {$this->unique_column_name} = LAST_INSERT_ID()");
        }

        $instance = $last_inserted->fetchAssociative();
        if ($instance) {
            return $this->resultFormatter($instance, false, false);
        }
    }

    public function select(array|string $fields)
    {
        $this->selectQuery($fields, $this->table);
        return $this;
    }


    public function update($data = [], $value = null)
    {
        $newdata = $this->dataFormatChecker($data, $value);

        $unique_column_name = $this->getUniqueColumn();

        $this->updateQuery($newdata, $this->table);

        if (!strpos(strtolower($this->getSql()), "where")) {
            $this->where($unique_column_name, $this->$unique_column_name);
        }

        if ($this->save()) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }

            return true;
        }

        return false;
    }


    public function delete($key = null, $value = null)
    {
        $unique_column_name = $this->getUniqueColumn();

        if ($key !== null && is_string($key) && $value === null) {
            if (isset($this->$key)) {
                $value = $this->$key;
            }
        } else if ($key === null && $value === null) {
            $key = $unique_column_name;
            if (isset($this->$unique_column_name)) {
                $value = $this->$unique_column_name;
            }
        }

        $statement = $this->query("DELETE FROM {$this->table} WHERE {$key} = '{$value}'");

        $this->clearInitalQuery();
        return $statement;
    }


    public function truncate()
    {
        if (in_array($this->driver, ["sqlite", "pdo_sqlite"])) {
            return $this->query("DELETE FROM $this->table");
        }
        $this->connection()->executeQuery("TRUNCATE $this->table");
    }


    public function search($keys, $value = null, $opration = ['%', '%'])
    {
        if (is_array($keys)) {
            if ($value != null && is_array($value)) {
                $opration = $value;
            }
        }

        $this->searchQuery($keys, $value, $opration);
        return $this;
    }

    public function orSearch($keys, $value = null, $opration = ['%', '%'])
    {
        if (is_array($keys)) {
            if ($value != null && is_array($value)) {
                $opration = $value;
            }
        }

        $this->orSearchQuery($keys, $value, $opration);
        return $this;
    }


    public function where($keys, $value = null)
    {
        $this->whereQuery($keys, $value);
        return $this;
    }

    public function orWhere($key, $value = null)
    {
        $this->orWhereQuery($key, $value);
        return $this;
    }


    public function whereWithOperation($keys, $opration, $value = null)
    {
        $this->whereQuery($keys, $value, $opration);
        return $this;
    }

    public function join(string $table, \Closure $callback)
    {
        $_table = $this->getTableName();
        $this->builder->join($_table, $table, '');

        $callback($this);

        return $this;
    }

    public function leftJoin(string $table, \Closure $callback)
    {
        $_table = $this->getTableName();
        $this->builder->leftJoin($_table, $table, '');

        $callback($this);

        return $this;
    }

    public function rightJoin(string $table, \Closure $callback)
    {
        $_table = $this->getTableName();
        $this->builder->leftJoin($_table, $table, '');

        $callback($this);

        return $this;
    }

    public function innerJoin(string $table, \Closure $callback)
    {
        $_table = $this->getTableName();
        $this->builder->innerJoin($_table, $table, '');

        $callback($this);

        return $this;
    }

    public function get()
    {
        return $this->select($this->selectedColumns)->fetch();
    }


    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }


    protected function resultFormatter($result, $multiple = false)
    {
        $data = [];
        $class = get_class($this);

        if ($this->result_data_format == "object") {
            if ($multiple == true) {
                foreach ($result as $instance) {
                    $class = $this->newObject($class, $instance);
                    array_push($data, $class);
                }
                return $data;
            }

            return $this->newObject($class, $result);
        } else if ($this->result_data_format == "arrays") {
            return $result;
        }
    }

    protected function newObject($name, $instance)
    {
        $class = new $name;
        $class->table($this->table);

        foreach ($instance as $key => $value) {
            $class->$key = $value;
        }

        return $class;
    }


    protected function bind($statement, $params)
    {

        foreach ($params as $key => $value) {

            $statement->bindValue(($key + 1), $value);
        }
        return $statement;
    }

    protected function fetch($clear = true)
    {
        $data = count($this->parameters) ? $this->parameters : null;
        $result = $this->query($this->getSql(), $data);
        if ($result) {

            ($clear === true ? $this->clearInitalQuery() : null);

            $data = $result->fetchAllAssociative();
            $count = count($data);

            if ($this->countOnly) {
                return $count;
            }

            if ($count > 0) {
                return ($count > 1)
                    ? $this->resultFormatter($data, true)
                    : $this->resultFormatter($data[0], false);
            }
        }

        return null;
    }


    protected function run($queryString)
    {

        $statement = $this->connection()->prepare($queryString);

        if ($statement->executeQuery()) {
            $this->clearInitalQuery();
            return true;
        }

        return false;
    }


    protected function save()
    {

        if ($this->builder) {

            if (count($this->parameters)) {
                $statement = $this->connection()->prepare($this->getSql());
                $statement = $this->bind($statement, $this->parameters);
                $result = $statement->executeQuery();
            } else {

                $result = $this->builder->executeQuery();
            }

            $this->clearInitalQuery();

            if ($result) {
                return true;
            }
        }

        return false;
    }


    public function table($name)
    {
        $this->setTableName($name);
        return $this;
    }

    public function getTableName()
    {
        return $this->table;
    }

    protected function setTableName($name = null)
    {
        if (!is_null($name)) {
            $this->table = $name;
            return;
        }

        if ($this->table == null) {
            $namespace = explode("\\", get_class($this));
            $namespace = str_split(end($namespace));

            $format = "";
            foreach ($namespace as $key => $char) {
                if (ctype_upper($char)) {
                    $format .= "_" . strtolower($char);
                    continue;
                }

                $format .= $char;
            }

            $table = trim($format, "_");
            $lastchar = strtolower(substr($table, -1));

            if ($lastchar == "y" && !in_array($table, $this->specialTableChars)) {
                $table = substr($table, 0, (strlen($table) - 1)) . "ies";
            } else if ($lastchar == "x" && !in_array($table, $this->specialTableChars)) {
                $table .= "es";
            } else if ($lastchar != "s" && !in_array($table, $this->specialTableChars)) {
                $table .= "s";
            }

            $this->table = $table;
        }
    }


    public function query($querystring, $data = null)
    {

        if ($querystring !== "") {

            $statement = $this->connection()->prepare($querystring);

            ($data !== null)
                ? $result = $statement->executeQuery($data)
                : $result = $statement->executeQuery();

            $this->clearInitalQuery();
            return $result;
        }

        return null;
    }


    public function dropTable($name = null)
    {

        if ($name !== null) {
            $this->table($name);
        }

        if (in_array($this->driver, ["sqlite", "pdo_sqlite", "mysqli"])) {

            $this->query("DROP TABLE IF EXISTS $this->table;");
            $this->query("DROP TABLE IF EXISTS $this->table;");
            return;
        }

        $this->query("SET FOREIGN_KEY_CHECKS = 1; DROP TABLE IF EXISTS $this->table;");
        $this->query("SET FOREIGN_KEY_CHECKS = 0; DROP TABLE IF EXISTS $this->table;");
    }


    public function getUniqueColumn()
    {
        return $this->unique_column_name;
    }

    public function setUniqueColumn($name)
    {
        $this->unique_column_name = $name;
    }


    protected $specialTableChars = ["boy"];
}
