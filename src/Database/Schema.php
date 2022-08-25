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



    public function __construct(Connection $connection = null)
    {
        $this->connection = $connection ?? $this->getSocket();
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


    public function attachClass($all = false, $fields = null)
    {
        if ($all == true) {
            $this->allQuery($this->table);
            $data = $this->fetch(false);

            return is_null($data)
                ? $data
                : (!is_array($data) ? array($data) : $data);
        } else {

            if ($this->fieldFormatChecker($fields)) {
                $this->selectQuery($this->fields, $this->table);
                return $this->fetch(false);
            }
        }
    }


    protected function positionCollection($key, $value)
    {

        if (!is_null($key) && !is_null($value)) {

            $result =  $this->where($key, $value)->get();
        } else {

            $result = $this->get();
        }

        return $result;
    }


    public function all()
    {
        $this->allQuery($this->table);
        $data = $this->fetch();

        if (is_null($data)) {
            return $data;
        }

        if (!is_array($data)) {
            return array($data);
        } else {
            return $data;
        }
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
        (empty($this->getSql())) ? $this->allQuery($this->table) : null;
        return $this->counter();
    }


    public function clearInitalQuery()
    {
        $this->parameters = [];
        $this->resetBuilder();
    }


    public function first($key = null, $value = null)
    {
        $result = $this->positionCollection($key, $value);

        if ($this->resultTypeChecker($result) == "object") {
            return $result;
        }

        if ($result != null && is_array($result)) {

            return array_shift($result);
        }

        return null;
    }


    public function last($key = null, $value = null)
    {
        $result = $this->positionCollection($key, $value);

        if ($this->resultTypeChecker($result) == "object") {
            return $result;
        }

        if ($result != null && is_array($result)) {
            return array_pop($result);
        }

        return null;
    }


    public function find($key, $value = null)
    {
        if ($value == null) {
            $this->result = $this->where("id", $key)->get();
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


    public function sum($column)
    {
        $data = function () {
            if ($this->parameters) {
                return $this->parameters;
            } else {
                return null;
            }
        };

        $this->sumQuery($column, $this->table);

        $result = $this->fetch();
        return $result;
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

        $this->allQuery($this->table);
        $this->orderQuery("id", "asc", $limits);

        $result = $this->fetch(false, false);

        if (!is_null($result) && !is_array($result)) {
            $result = array($result);
        } else if (!is_null($result)) {
            $result = $result;
        }

        $total_result = 0;

        $total_pages = floor($total_result / $number);
        $rem = ($total_result % $number);
        if ($rem > 0) {
            $total_pages += 1;
        }

        if ($to > $total_result) {
            $to = $total_result;
        }
        if ($total_result == 0) {
            $from  = 0;
        }

        $data = [
            "current_page" => $page,
            "page" => $page,
            "start_at" => $from,
            "end_at" => $to,
            "total" => $total_result,
            "pages" => $total_pages,
            "count" => $number
        ];

        $response = json_encode($data);
        setEnv("_pagination", $response);

        return $result;
    }

    public function create(array $data, callable|null $callback = null)
    {
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
            $last_inserted = $this->query("SELECT * FROM $this->table WHERE id = last_insert_rowid()");
        } else {
            $last_inserted = $this->query("SELECT * FROM $this->table WHERE id = LAST_INSERT_ID()");
        }

        return $this->resultFormatter($last_inserted->fetchAssociative(), false, false);
    }

    public function select(array|string $fields)
    {
        $this->selectQuery($fields, $this->table);
        return $this;
    }


    public function update($data, $value = null)
    {
        $data = $this->dataFormatChecker($data, $value);
        $this->updateQuery($data, $this->table);

        if (!strpos(strtolower($this->getSql()), "where")) {

            return $this->where("id", $this->id)->update($data);
        } else {

            if ($this->save()) {

                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }

                return true;
            }
        }

        return false;
    }


    public function delete($key = null, $value = null)
    {

        if (is_null($key) && is_null($value)) {
            if (isset($this->id)) {
                $key = "id";
                $value = $this->id;
            } else {
                // throwable error for parameter expected
            }
        } elseif (is_null($value)) {
            $value = $key;
            $key = "id";
        }

        $data = $this->dataFormatChecker($key, $value);
        if ($this->deleteQuery($data, $this->table)) {

            $statement = $this->connection()->prepare($this->getSql());
            if (count($this->parameters))
                if ($statement->executeQuery($this->parameters)) {
                    return true;
                }

            return $statement->executeQuery();
        }

        return false;
    }


    public function truncate()
    {
        if (in_array($this->driver, ["sqlite", "pdo_sqlite"])) {
            return $this->delete();
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


    public function where($keys, $value = null)
    {
        $this->whereQuery($keys, $value);
        return $this;
    }

    public function orWhere($keys, $value = null)
    {
        $this->orWhereQuery($keys, $value);
        return $this;
    }


    public function whereWithOperation($keys, $opration, $value = null)
    {
        $this->whereQuery($keys, $value, $opration);
        return $this;
    }


    public function get()
    {
        return $this->select("*")->fetch();
    }


    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }


    protected function resultFormatter($result, $multiple = false, $relations = false)
    {
        $data = [];
        $class = get_class($this);

        if ($this->result_data_format == "object") {
            if ($multiple == true) {
                foreach ($result as $instance) {
                    $class = $this->newObject($class, $instance, $relations);
                    array_push($data, $class);
                }
                return $data;
            }

            return $this->selfObject($result, $relations);
        } else if ($this->result_data_format == "arrays") {
            return $result;
        }
    }

    protected function selfObject($instance, $relations = false)
    {
        foreach ($instance as $key => $value) {
            $this->$key = $value;
        }

        if ($this instanceof Model && $relations == true) {
            $this->bootRelations($this);
        }

        return $this;
    }

    protected function newObject($name, $instance, $relations = false)
    {
        $class = new $name;
        foreach ($instance as $key => $value) {
            $class->$key = $value;
        }

        if ($this instanceof Model && $relations == true) {
            $this->bootRelations($class);
        }

        return $class;
    }

    protected function fetch($relations = true, $clear = true)
    {
        if ($this->builder) {

            $statement = $this->connection()->prepare($this->builder->getSql());

            (count($this->parameters))
                ? $result = $statement->executeQuery($this->parameters)
                : $result = $statement->executeQuery();


            if ($result) {
                $data = $result->fetchAllAssociative();
                ($clear === true ? $this->clearInitalQuery() : null);
                if (count($data) > 0) {

                    return (count($data) > 1)
                        ? $this->resultFormatter($data, true, $relations)
                        : $this->resultFormatter($data[0], false, $relations);
                }
            }
        }

        return null;
    }


    protected function counter($direct = false)
    {

        ($direct === false) ? $this->builder : null;
        $statement = $this->connection()->prepare($this->getSql());

        (isset($this->whereData))
            ? $exec = $statement->executeQuery($this->parameters)
            : $exec = $statement->executeQuery();

        if ($exec) {
            $this->clearInitalQuery();
            return ($statement->rowCount());
        }

        return 0;
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

        if (!is_null($this->builder)) {

            if (count($this->parameters)) {

                foreach ($this->parameters as $key => $value) {
                    $this->builder->setParameter($key, $value);
                }
            }

            $exec = $this->connection()->prepare($this->getSql());

            if ($exec->executeQuery()) {
                $this->clearInitalQuery();
                return true;
            }

            return false;
        }
    }


    public function table($name)
    {
        return $this->setTable($name);
    }


    protected function setTable($name)
    {
        $this->table = $name;
        return $this;
    }

    protected function useTable()
    {
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


    public function dropTable($name = null) {

        if($name !== null) {
            $this->table($name);
        }

        if(in_array($this->driver, ["sqlite", "pdo_sqlite", "mysqli"])) {

            $this->query("DROP TABLE IF EXISTS $this->table;");
            $this->query("DROP TABLE IF EXISTS $this->table;");
            return;
        }

        $this->query("SET FOREIGN_KEY_CHECKS = 1; DROP TABLE IF EXISTS $this->table;");
        $this->query("SET FOREIGN_KEY_CHECKS = 0; DROP TABLE IF EXISTS $this->table;");
    }


    protected $specialTableChars = ["boy"];
}
