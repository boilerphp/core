<?php

namespace Boiler\Core\Database;

use Boiler\Core\Configs\GlobalConfig;
use ErrorException, PDO;

class Connection
{

    /**
     * database driver
     *
     * @var string
     *
     */
    private $driver = "mysql";


    /**
     * database hostname
     *
     * @var string
     *
     */
    private $host;


    /**
     * database username
     *
     * @var string
     *
     */
    private $username;


    /**
     * database password
     *
     * @var string
     *
     */
    private $password;


    /**
     * database name
     *
     * @var string
     *
     */
    private $dbname;


    /**
     * database port
     *
     * @var integer
     *
     */
    private $port;


    /**
     * database connection props index
     *   
     * @var integer
     *
     */
    private $dbConnection;


    /**
     * datasource for connection
     *   
     * @var string
     *
     */
    private $dataSource;

    /**
     * database connection
     *   
     * @var mixed
     *
     */
    protected $connection;


    public function getConnectionSocket()
    {
        return $this->connection;
    }

    public function closeConnectionSocket()
    {
        $this->connection = null;
    }

    public function connect()
    {
        if (!isset($this->connection)) {

            try {

                if (env('APP_ENV') != 'testing') {
                    list($this->host, $this->username, $this->password, $this->dbname, $this->port) = $this->getConnectionVariable();
                }

                $this->buildConnectionString();

                $this->connection = new PDO($this->dataSource, $this->username, $this->password);

                // Set all attributes
                $this->connection->setAttribute(PDO::ATTR_TIMEOUT, 5);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->connection->setAttribute(PDO::ATTR_PERSISTENT, true);
                $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            } catch (\PDOException $pd) {
                throw $pd;
                exit;
            }
        }
    }

    protected function buildConnectionString()
    {
        if (env('APP_ENV') == 'testing' && env('DB_CONNECTION') == 'sqlite') {
            $this->driver = env('DB_CONNECTION');
            $this->dataSource = env('DB_CONNECTION') . ':' . (env('DB_DATABASE') == ':memory:' ? env('DB_DATABASE') : __DIR__ . '../../' . env('DB_DATABASE'));
            return;
        }

        $this->dataSource = $this->driver . ":host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname;
    }

    protected function checkDatabaseVariables(array $variables, object $dbConnectionVariables)
    {
        foreach ($variables as $variable) {
            if (!isset($dbConnectionVariables->$variable)) {
                throw new ErrorException($variable . " not found in database connection.");
            }
        }
    }

    protected function getConnectionVariable()
    {
        $app_config = GlobalConfig::getAppConfigs();

        if ($this->getDbSelection($app_config)) {
            $app_config->databaseConnection = $this->selectedConnectionVariables;
        } else {
            $app_config->databaseConnection = $app_config->databaseConnections->default;
        }

        $this->checkDatabaseVariables(
            ["host", "username", "password", "database", "port"],
            $app_config->databaseConnection
        );

        $this->driver = (isset($app_config->databaseConnection->driver))
            ? $app_config->databaseConnection->driver
            : $this->driver;

        $host = $app_config->databaseConnection->host;
        $username = $app_config->databaseConnection->username;
        $password = $app_config->databaseConnection->password;
        $dbname = $app_config->databaseConnection->database;
        $port = $app_config->databaseConnection->port;

        return [$host, $username, $password, $dbname, $port];
    }

    protected function getDbSelection($app_config)
    {

        if ($this->dbConnection != null) {
            # ch3ck if database connection name exists
            $n = $this->dbConnection;
            if (!isset($app_config->databaseConnections->$n)) {
                # throw undefined connection name error;
                exit;
            }

            $this->selectedConnectionVariables = $app_config->databaseConnections->$n;
            return true;
        }

        return false;
    }

    public function setTarget($target_name)
    {
        $this->dbConnection = $target_name;
    }

    protected function useDriver($driver)
    {
        $this->driver = $driver;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function getDbName()
    {
        return $this->dbname;
    }

    public function __sleep()
    {
        return ["connection"]; //Pass the names of the variables that should be serialised here
    }

    public function __wakeup()
    {
        //Since we can't serialize the connection we need to re-open it when we unserialise
        $this->connect();
    }
}
