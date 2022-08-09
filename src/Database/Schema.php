<?php

namespace Boiler\Core\Database;

use ReflectionClass;
use ReflectionMethod;
use Boiler\Core\Configs\GlobalConfig;
use Doctrine\DBAL\DriverManager;

class Schema extends QueryBuilder
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


    /**
     * Database connection socker 
     *
     * @var PDO
     *
     */
    protected $socket;


    public function __construct()
    {
        $this->clearInitalQuery();
    }


    public function connection(string | null $key = null)
    {
        $this->clearInitalQuery();

        if (!is_null($key)) {
            $this->useTable();
            return GlobalConfig::setTarget($key);
        }

        return GlobalConfig::getAppConnetion();
    }


    public function attachClass($all = false, $fields = null)
    {
        if ($all == true) {
            $this->allQuery();
            $data = $this->fetch(false);

            return is_null($data)
                ? $data
                : (!is_array($data) ? array($data) : $data);
        } else {

            if ($this->fieldFormatChecker($fields)) {
                $this->selectQuery($this->fields);
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
        $this->allQuery();
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
        (empty($this->queryString)) ? $this->allQuery() : null;
        return $this->counter();
    }


    public function clearInitalQuery()
    {
        $this->queryString = "";
        $this->whereQuery = "";
        $this->whereData = [];
        $this->orderQuery = "";
        $this->groupQuery = "";
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

            if ($this->whereData) {
                return $this->whereData;
            } else {
                return null;
            }
        };

        $statement = $this->query($this->sumQuery($column), $data());
        return ($statement->fetch()->$column ?? 0);
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

        $this->allQuery();
        if ($this->orderQuery == "") {
            $this->orderQuery("id", "asc", $limits);
        } else {
            $this->limits($start, $number);
        }

        $result = $this->fetch(false, false);

        if (!is_null($result) && !is_array($result)) {
            $result = array($result);
        } else if (!is_null($result)) {
            $result = $result;
        }

        $total_result = 0; //$this->counter(true);

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

    public static function createDatabase($name) {
        
        // getSocket()->executeQuery("CREATE DATABASE `$name`");
    }

    protected function insert(array $data)
    {
        if ($data) {
            if ($this->insertQuery($data)) {

                $statement = $this->getSocket()->prepare($this->queryString);
                if ($statement->execute($data)) {
                    $exec = $this->query("SELECT * FROM $this->table WHERE id = LAST_INSERT_ID()");
                    if ($exec) {
                        if ($exec->rowCount() > 0) {
                            return $this->resultFormatter($exec->fetch(), false, false);
                        }
                    }
                }
            }
        }

        return null;
    }


    public function select($fields = null)
    {

        if ($this->fieldFormatChecker($fields)) {
            $this->selectQuery($this->fields);
        }

        return $this;
    }


    public function update($data, $value = null)
    {
        if ($this->dataFormatChecker($data, $value)) {
            if ($this->updateQuery($this->data)) {

                if (empty($this->whereQuery)) {
                    return $this->where("id", $this->id)->update($this->data);
                } else {
                    if ($this->save()) {
                        
                        foreach ($this->data as $key => $value) {
                            $this->$key = $value;
                        }

                        return true;
                    }
                }
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

        if ($this->dataFormatChecker($key, $value)) {
            if ($this->deleteQuery($this->data)) {

                $statement = $this->getSocket()->prepare($this->queryString);
                if ($statement->execute($this->whereData)) {
                    return true;
                }
            }
        }

        return false;
    }


    public function truncate() {
        $this->run("TRUNCATE $this->table");
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
        return $this->select()->fetch();
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


    public function getSocket()
    {

        if ($this->database !== null && $this->database !== 'default') {

            return GlobalConfig::setTarget($this->database)->getConnectionSocket();
        } else {

            if (!GlobalConfig::$IS_CONNECTED) {
                /**
                 * Connecting app to database
                 * 
                 */

                GlobalConfig::setAppConnetion();
            }

            return GlobalConfig::getAppConnetion()->getConnectionSocket();
        }
    }

    protected function fetch($relations = true, $clear = true)
    {
        if ($this->queryString()) {

            $statement = $this->getSocket()->prepare($this->queryString);

            (count($this->whereData))
                ? $exec = $statement->execute($this->whereData)
                : $exec = $statement->execute();

            if ($exec) {
                ($clear === true ? $this->clearInitalQuery() : null);
                if ($statement->rowCount() > 0) {

                    return ($statement->rowCount() > 1)
                        ? $this->resultFormatter($statement->fetchAll(), true, $relations)
                        : $this->resultFormatter($statement->fetch(), false, $relations);
                }
            }
        }

        return null;
    }


    protected function counter($direct = false)
    {

        ($direct === false) ? $this->queryString() : null;
        $statement = $this->getSocket()->prepare($this->queryString);

        (isset($this->whereData))
            ? $exec = $statement->execute($this->whereData)
            : $exec = $statement->execute();

        if ($exec) {
            $this->clearInitalQuery();
            return ($statement->rowCount());
        }

        return 0;
    }


    protected function run($queryString)
    {

        $statement = $this->getSocket()->prepare($queryString);

        if ($statement->execute()) {
            $this->clearInitalQuery();
            return true;
        }

        return false;
    }


    protected function save()
    {

        if ($this->queryString()) {

            $statement = $this->getSocket()->prepare($this->queryString);

            (count($this->whereData))
                ? $exec = $statement->execute($this->whereData)
                : $exec = $statement->execute();

            if ($exec) {
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

            $statement = $this->getSocket()->prepare($querystring);

            ($data !== null)
                ? $statement->execute($data)
                : $statement->execute();

            $this->clearInitalQuery();
            return $statement;
        }

        return null;
    }


    public function dropDatabaseTable($table)
    {

        $this->query($this->dropDatabaseTableQuery($table));
    }


    protected $specialTableChars = ["boy"];
}
