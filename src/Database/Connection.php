<?php

namespace Boiler\Core\Database;

use Boiler\Core\Configs\GlobalConfig;
use Doctrine\DBAL\DriverManager;
use ErrorException, PDO;

class Connection
{

    /**
     * database driver
     *
     * @var string
     *
     */
    private $driver = "pdo_mysql";


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

    /**
     * selected database connection variables
     *   
     * @var mixed
     *
     */
    protected $selectedConnectionVariables;

    public function getConnection()
    {
        return $this->connection;
    }

    public function closeConnection()
    {
        $this->connection = null;
    }

    public function connect()
    {
        if (!isset($this->connection)) {

            try {

                $this->buildConnectionString();

                if($this->driver === "sqlite" || $this->driver === "pdo_sqlite") {

                    $connParams = [
                        'url' => $this->dataSource,
                    ];

                } else {

                    $connParams = [
                        'dbname' => $this->dbname,
                        'user' => $this->username,
                        'password' => $this->password,
                        'host' => $this->host,
                        'driver' => $this->driver,
                    ];
                }
                
                $this->connection = DriverManager::getConnection($connParams);

            } catch (\Exception $ex) {
                throw $ex;
                exit;
            }
        }
    }

    protected function buildConnectionString()
    {
        if (env('APP_ENV') == 'testing' && env('DB_CONNECTION') == 'sqlite') {
            $this->driver = env('DB_CONNECTION');
            $this->dataSource = env('DB_CONNECTION') . ':///' . (env('DB_DATABASE') == ':memory:' ? env('DB_DATABASE') : './' . env('DB_DATABASE'));
            return;
        }

        list($this->host, $this->username, $this->password, $this->dbname, $this->port) = $this->getConnectionVariable();
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
