<?php


namespace Boiler\Core\Console;

use Console\Support\Actions;

class Command extends Actions
{


    public $commands = array(
        "create",
        "start",
        "db",
        "activate",
        "disable",
        "migrate",
        "run:test"
    );

    public function __construct()
    {
    }

    /*
    * ---------------------------------------- 
    * Start Server using command line manager
    * ----------------------------------------
    */
    public function start(...$parameters)
    {
        $flags = $parameters[0];
        $host = '127.0.0.1';
        $port = 8000;

        foreach ($flags as $flag) {
            if (preg_match('/\-\-port\=/', $flag)) {
                $port = str_replace('--port=', '', $flag);
            }
            if (preg_match('/\-\-host\=/', $flag)) {
                $host = str_replace('--host=', '', $flag);
            }
        }
        $this->runServer($host, $port);
    }

    /*
    * --------------------------------------------------------------
    * Create Project [Controller, Models, Migration, Notifications] 
    * using command line manager, files will be generated.
    * -------------------------------------------------------------
    */
    public function create(...$parameters)
    {
        $action = isset($parameters[0][0]) ? $parameters[0][0] : null;
        $name = isset($parameters[0][1]) ? $parameters[0][1] : null;
        $flag = isset($parameters[0][2]) ? $parameters[0][2] : null;

        $this->arg_string = implode("|", $parameters[0]);

        if ($action != null) {
            if ($flag != null) {
                if (array_key_exists($flag, $this->flags)) {
                    $this->{$action}($name, $flag);
                }
            } else {
                $this->{$action}($name);
            }
        }
    }


    /*
    * --------------------------------------------------------------
    * Performs Database actions 
    * using command line manager.
    * -------------------------------------------------------------
    */
    public function db(...$parameters)
    {
        $action = isset($parameters[0][0]) ? $parameters[0][0] : null;

        if ($action != null) {
            $this->{$action}($parameters[0]);
        }
    }

    /*
    * --------------------------------------------------------------
    * Performs Migration actions 
    * using command line manager.
    * -------------------------------------------------------------
    */
    public function migrate(...$parameters)
    {
        $flags = $parameters[0];

        $this->pathHandler($flags, "migration");

        $this->fresh = in_array('--new', $flags) || in_array('--fresh', $flags);
        $this->force = in_array('--force', $flags) || in_array('-f', $flags);
        $this->rollback = in_array('--rollback', $flags);
        $this->hasRollbackSteps = preg_match('/--steps=(.*)/', implode(' ', $flags));
        $this->hasRollbackTarget = preg_match('/--target=(.*)/', implode(' ', $flags));

        $target = null;
        $steps = 1;

        if ($this->hasRollbackSteps) {
            $steps = preg_replace('/(.*)--steps=(.*) (.*)/', '$2', implode(' ', $flags));
        }
        if ($this->hasRollbackTarget) {
            $target = preg_replace('/(.*)--target=(.*) (.*)/', '$2', implode(' ', $flags));
        }

        $this->migrationReflection();

        if ($this->fresh) {
            $this->dropAllExistingTable();
        }
        if ($this->rollback) {
            $this->rollbackMigrations($target, $steps);
            exit;
        }


        if ($this->newMigrationsChecker()) {
            if (!$this->checkTableExists("migrations")) {
                $this->createMigrationsTable();
            }

            $this->runMigrations();

            return true;
        }

        verbose("No new migrations", "success");
    }


    /*
    * --------------------------------------------------------------
    * Performs activation activities 
    * including third party libraries.
    * -------------------------------------------------------------
    */
    public function activate(...$parameters)
    {
        $action = isset($parameters[0][0]) ? $parameters[0][0] : null;
        $flag = isset($parameters[0][1]) ? $parameters[0][1] : null;

        if ($action != null) {
            $action = str_replace("-", "", $action);

            if ($flag != null) {
                if (array_key_exists($flag, $this->db_flags)) {
                    $this->$action(true, $flag);
                }
            } else {
                $this->$action(true);
            }
        }
    }


    /*
    * --------------------------------------------------------------
    * disable libraries and activities 
    * remove unwanted configurations.
    * -------------------------------------------------------------
    */
    public function disable(...$parameters)
    {
        $action = isset($parameters[0][0]) ? $parameters[0][0] : null;
        $flag = isset($parameters[0][1]) ? $parameters[0][1] : null;

        if ($action != null) {
            $action = str_replace("-", "", $action);

            if ($flag != null) {
                if (array_key_exists($flag, $this->db_flags)) {
                    $this->$action(false, $flag);
                }
            } else {
                $this->$action(false);
            }
        }
    }


    /*
    * --------------------------------------------------------------
    * disable libraries and activities 
    * remove unwanted configurations.
    * -------------------------------------------------------------
    */
    public function app(...$parameters)
    {
        $action = isset($parameters[0][0]) ? $parameters[0][0] : null;
        $flags = array_shift($parameters[0]);

        if ($action != null) {
        }
    }

    /*
    * --------------------------------------------------------------
    * disable libraries and activities 
    * remove unwanted configurations.
    * -------------------------------------------------------------
    */
    public function runTest(...$parameters)
    {
        $flags = array_shift($parameters);
        $this->runAppTest($flags);
    }
}
