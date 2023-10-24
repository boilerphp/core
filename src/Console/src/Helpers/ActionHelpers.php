<?php

namespace Console\Support\Helpers;

use Boiler\Core\Console\Console;
use Boiler\Core\FileSystem\Fs;
use Boiler\Core\Database\Console\MigrationReflection;
use Boiler\Core\Database\Migration\Table;
use Console\Support\Interfaces\ActionHelpersInterface;

class ActionHelpers implements ActionHelpersInterface
{


    public $arg_string = "";
    public $verbose = true;

    protected $module;
    protected $component;
    protected $use_namespace;
    protected $new_migrations;
    protected $controller_name;
    protected $migrationReflection;


    public $flags = array(
        "--m" => "model",
        "--a" => "all",
        "--c" => "controller",
        "--d" => "migration",
        "--s" => "socket"
    );

    public $db_flags = array(
        "--new" => "refresh",
        "--rollback" => "rollback",
        "--backup" => "backup",
    );

    public $configurations = array(
        "model" => "configiureModel",
        "controller" => "configureController",
        "migration" => "configureMigration",
        "job" => "configureJob",
        "notification" => "configureNotification",
        "socket" => "configureSocket",
        "seeder" => "configureSeeder"
    );

    public $db_configurations = array(
        "refresh" => "dropAllExistingTable",
        "rollback" => "rollbackMigrations",
    );

    public $paths = array(
        "model" => "./app/Models/",
        "view" => "./app/Views/",
        "controller" => "./app/Controllers/",
        "middleware" => "./app/Middlewares/",
        "job" => "./app/Jobs/",
        "migration" => "./database/Migrations/",
        "notification" => "./app/Notifications/",
        "seeder" => "./database/Seeders/",
        "tests" => "./tests/"
    );

    protected $specialTableChars = ["boy"];


    public function __construct($verbose = true)
    {
        $this->$verbose = $verbose;
    }


    public function migrationReflection()
    {
        $this->migrationReflection = new MigrationReflection($this->verbose);
    }

    /**
     * Starts Application Development Server
     * 
     * @param $port - default port number is 8000
     * 
     * @return void
     */
    public function runServer($host, $port)
    {
        $server_command = "php -S " . $host . ":" . $port . " -t ./www";
        $this->verbose("Server listening on http://{$host}:{$port}", "success");
        exec($server_command);
    }


    public function runAppTest($flags)
    {
        $target = null;
        $type = 'Unit';
        $config = './phpunit.xml';

        if ($flags !== null) {
            foreach ($flags as $flag) {

                list($name, $value) = explode('=', $flag);

                if ($name === '--test') {
                    $target = $value;
                }

                if ($name === '--type') {
                    $type = $value;
                }
            }
        }

        if ($target) {
            return (new Console())->exec("php ./vendor/bin/phpunit -c {$config} --testsuite={$type} ./tests/{$type}/{$target}.php");
        }

        return (new Console())->exec("php ./vendor/bin/phpunit -c {$config} --testsuite={$type}");
    }

    /**
     * checks flag and action
     * for difference
     * @return bool
     */

    public function flagchecker($action, $flag)
    {

        if ($this->flags[$flag] == $action) {
            $this->verbose("mis-usage of flag on create " . $action);
            return false;
        }

        return true;
    }

    public function flagHandler($name, $flag, $action)
    {

        if ($flag == "--a") {
            foreach ($this->flags as $flag => $task) {
                if ($task == $action || $task == "all") {
                    continue;
                }

                $this->flagConfig($flag, $name);
            }
        } else {
            $this->flagConfig($flag, $name);
        }
    }

    public function flagConfig($flag, $name)
    {
        $task = $this->flags[$flag];
        $configuration = $this->configurations[$task];

        if ($task == "controller") {
            $name .= "Controller";
        }

        $path = $this->path($task) . $name . ".php";

        if ($task == "migration") {
            $name = $this->tableFormating($name);

            $file_name = $name . "_table.php";

            if ($this->checkMigrationExistent($file_name)) {
                $this->verbose("Migration already exists");
                return;
            }

            $path = $this->path($task) . time() . "_" . $file_name;
            $this->$configuration($name, $path, $component = "migration");
            return;
        }

        $this->$configuration($name, $path);
    }

    public function checkExistent($path)
    {
        if (file_exists($path)) {
            return true;
        }
        return false;
    }

    public function path($name)
    {
        return $this->paths[$name];
    }


    public function checkMigrationExistent($filename)
    {

        $all_migrations_file = glob("./database/Migrations/*.php");
        if ($all_migrations_file) {
            foreach ($all_migrations_file as $migration_file) {
                if ($this->migrationFileNameChecker($migration_file, $filename)) {
                    $this->verbose("Migration already exists");
                    exit;
                }
            }
        }
        return false;
    }


    public function migrationFileNameChecker($migration_file, $name_format)
    {
        $ex = explode("/", $migration_file);
        $exMfile = explode("_", end($ex));
        $filename = $exMfile[1] . "_" . $exMfile[2];

        if ($filename == $name_format) {
            return true;
        }

        return false;
    }

    /**
     * usage: configures notification structure and inital setup
     * 
     * @param string $name
     * 
     * @param string $path
     * 
     * @return void
     */

    public function configureNotification($name, $path)
    {
        $component_path = __DIR__ . "/../../components/notification.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[Notification\]/", $name, $this->component);
            if ($this->writeModule($path)) {
                $this->verbose("$name successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures model structure and inital setup
     * @param string $name
     * 
     * @param string $path
     * 
     * @return bool
     */

    public function configureModel($name, $path)
    {
        $component_path = __DIR__ . "/../../components/model.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[Model\]/", $name, $this->component);
            if ($this->writeModule($path)) {
                $this->verbose("$name model successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures middleware structure and inital setup
     * @param string $name
     * 
     * @param string $path
     * 
     * @return bool
     */

    public function configureMiddleware($name, $path)
    {
        $component_path = __DIR__ . "/../../components/middleware.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[Middleware\]/", $name, $this->component);
            if ($this->writeModule($path)) {
                $this->verbose("$name middleware successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures migration structure and inital setup
     * 
     * @param string $name
     * 
     * @param string $path
     * 
     * @param string $component
     */
    public function configureMigration($name, $path, $component)
    {
        $component_path = __DIR__ . "/../../components/$component.component";
        if ($this->readComponent($component_path)) {
            $class_name = ucfirst($name);
            if (strpos($name, "_")) {
                $e = explode("_", $name);
                $new_cl_name = "";
                foreach ($e as $piece) {
                    $new_cl_name .= ucfirst($piece);
                }

                $class_name = $new_cl_name;
            }

            if ($component !== "migration.alter") {
                $table_name = $name;
                $class_name .= 'Table';
            } else {
                $arg_explode = explode("|", $this->arg_string);
                $end_arg = end($arg_explode);

                if (preg_match("/\-\-/", $end_arg)) {
                    $this->verbose("Table name is required");
                    exit;
                }

                $table_name = $end_arg;
            }

            $this->module = preg_replace("/\[ClassName\]/", $class_name, $this->component);
            $this->module = preg_replace("/\[TableName\]/", strtolower($table_name), $this->module);

            if ($this->writeModule($path)) {
                $this->verbose("Created migration: $path");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures socket structure and inital setup
     * @param string $name
     * 
     * @param string $path
     * 
     * @return void;
     */

    public function configureSocket($name, $path)
    {
        $component_path = __DIR__ . "/../../components/websocket/socket-skeleton.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[SocketName\]/", $name, $this->component);
            if ($this->writeModule($path)) {
                $this->verbose("$name socket successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures job structure and inital setup
     * @param string $name
     * 
     * @param string $path
     * 
     * @return void;
     */

    public function configureJob($name, $path)
    {
        $component_path = __DIR__ . "/../../components/job.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[JobName\]/", $name, $this->component);
            if ($this->writeModule($path)) {
                $this->verbose("$name job successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: formats table name and file name
     * @param string $name
     * 
     * @return string table_name
     */
    public function tableFormating($name)
    {
        $format_name = str_split($name);
        $table_name = "";
        foreach ($format_name as $key => $val) {
            if (ctype_upper($val)) {
                $table_name .= "_" . strtolower($val);
                continue;
            }

            $table_name .= $val;
        }

        $table_name = trim($table_name, "_");

        $lastchar = strtolower(substr($table_name, -1));

        if ($lastchar == "y" && !in_array($table_name, $this->specialTableChars)) {
            $table_name = substr($table_name, 0, (strlen($table_name) - 1)) . "ies";
        } else if ($lastchar == "x" && !in_array($table_name, $this->specialTableChars)) {
            $table_name .= "es";
        } else if ($lastchar != "s" && !in_array($table_name, $this->specialTableChars)) {
            $table_name .= "s";
        }

        return $table_name;
    }

    /**
     * usage: checkes  is controller has namespace prefix
     * @param string controller_name
     * 
     * @return string namespace
     */
    public function checkNamaspacePrefix($_name)
    {
        if (strpos($_name, "\\") || strpos($_name, "/")) {

            $split = (strpos($_name, "/"))
                ? explode("/", $_name)
                : explode("\\", $_name);

            $_namespace = $split[0];
            $this->controller_name = $split[1];

            $folder = $this->path("controller") . $_namespace;
            if (!Fs::is_active_directory($folder)) {
                Fs::create_directory($folder);
            }

            $this->use_namespace = "\\" . $_namespace;

            return true;
        }

        return false;
    }


    /**
     * usage: configures controller structure and inital setup
     * @param string $name
     * 
     * @param string $path
     * 
     * @return bool
     */
    public function configureController($name, $path)
    {
        $component_path = __DIR__ . "/../../components/controller.component";

        if ($this->readComponent($component_path) !== "") {

            if ($this->checkNamaspacePrefix($name)) {

                $this->component = preg_replace("/\[Controller_Base_Namespace\]/", 'use App\Controllers\Controller;', $this->component);
                $this->component = preg_replace("/\[Namespace\]/", $this->use_namespace, $this->component);
                $name = $this->controller_name;
            } else {
                $this->component = preg_replace("/\[Controller_Base_Namespace\]/", '', $this->component);
                $this->component = preg_replace("/\[Namespace\]/", '', $this->component);
            }


            $this->module = preg_replace("/\[Controller\]/", $name, $this->component);
            $view_folder = str_replace("controller", "", strtolower($name));

            $this->module = preg_replace("/\[View\]/", $view_folder, $this->module);
            if ($this->writeModule($path)) {
                $this->verbose("$name successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures seeder structure and inital setup
     * @param string $seeder_name
     * 
     * @param string $seeder_path
     * 
     * @return void;
     */

    public function configureSeeder($seeder_name, $seeder_path)
    {
        $component_path = __DIR__ . "/../../components/seeder.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[ClassName\]/", $seeder_name, $this->component);
            if ($this->writeModule($seeder_path)) {
                $this->verbose("$seeder_name successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * usage: configures seeder structure and inital setup
     * @param string $seeder_name
     * 
     * @param string $seeder_path
     * 
     * @return void;
     */

    public function configureTest($test_name, $test_path, $unit = false)
    {
        $component_path = __DIR__ . "/../../components/test.component";

        if ($this->readComponent($component_path)) {
            $this->module = preg_replace("/\[ClassName\]/", $test_name, $this->component);
            if ($unit === true) {
                $this->module = preg_replace("/\\Integration/", "Unit", $this->module);
            }
            if ($this->writeModule($test_path)) {
                $this->verbose("$test_name successfully created!");
                return true;
            }
            return false;
        }
    }

    /**
     * reads the component file and get the components structure
     * @param string component_file_path
     * @return string
     */
    public function readComponent($path)
    {
        $this->component = file_get_contents($path);
        return $this->component;
    }



    public function writeModule($path)
    {
        $module = fopen($path, "w+");
        fwrite($module, $this->module);
        return fclose($module);
    }


    public function checkTableExists($table)
    {

        $this->migrationReflection->connection();
        $tables = $this->migrationReflection->getTables();

        if ($tables) {
            foreach ($tables as $key => $value) {
                if (array_values($value)[0] == $table) {
                    $state = true;
                    break;
                } else {
                    $state = false;
                }
            }
        } else {
            $state = false;
        }

        return $state;
    }


    public function dropAllExistingTable()
    {
        $this->migrationReflection->clearTable();
        $count  = $this->migrationReflection->dropAllTables();

        $this->verbose("Dropped {$count} table(s)", "info", true);
    }

    public function rollbackMigrations($path = null, $steps = 1)
    {

        $all_migrations_file = array_reverse(glob("./database/Migrations/*.php"));

        if ($all_migrations_file) {

            $index = 0;

            foreach ($all_migrations_file as $migration_file) {
                if ($index >= $steps) {
                    break;
                }

                // Check if migration exists 

                $this->requireOnce($migration_file);

                $class = $this->migrationReflection->migrationClass($migration_file);
                $name = $this->migrationReflection->migrationName($migration_file);

                if (!$this->migrationReflection->checkMigration($name)) {
                    continue;
                }


                $this->verbose("Rolling Back: ", "info", false);
                $this->verbose("{$name}");

                $class->out();

                $this->migrationReflection->deleteMigration($migration_file);

                $this->verbose("Rolled Back: ", "success", false);
                $this->verbose("{$name}");

                $index++;
            }
        }
    }

    public function newMigrationsChecker()
    {
        $this->new_migrations = array();
        $all_migrations_file = glob("./database/Migrations/*.php");

        if ($all_migrations_file) {
            foreach ($all_migrations_file as $migration_file) {
                if ($this->migrationWaitingMigrate($migration_file)) {
                    array_push($this->new_migrations, $migration_file);
                }
            }
        }

        if (count($this->new_migrations) > 0) {
            return true;
        }

        return false;
    }


    public function migrationWaitingMigrate($migration_file)
    {
        $ex = explode("/", $migration_file);
        $migration = str_replace(".php", "", end($ex));

        if ($this->isWaiting($migration)) {
            return true;
        }

        return false;
    }

    public function isWaiting($migration)
    {
        if ((new MigrationReflection($this->verbose))->checkMigration($migration)) {
            return false;
        }
        return true;
    }

    public function createMigrationsTable()
    {
        return $this->migrationReflection->init();
    }

    public function runMigrations()
    {
        $version = $this->migrationReflection->getCurrentMigrationVersion() + 1;

        foreach ($this->new_migrations as $migration) {
            $this->requireOnce($migration);

            $_tableName = $this->migrationReflection->mFileFormater($migration)["table"];
            $_fileName = $this->migrationReflection->mFileFormater($migration)["file"];

            $this->verbose("Migrating: ", "info", false);
            $this->verbose("{$_fileName}");

            $class = $this->migrationReflection->migrationClass($migration);
            $class->in();

            $this->registerMigration($_fileName, $version);
            $this->verbose("Migrated: ", "success", false);
            $this->verbose("{$_fileName}");
        }

        $this->verbose("Running migration alter queries...", "info");
        $this->runMigrationAlters();

        $this->verbose("Migration completed successfully", "success");
    }

    public function runMigrationAlters()
    {
        $alters = Table::getAlters();

        if (count($alters) > 0) {
            foreach ($alters as $query) {

                $this->migrationReflection->query($query);
            }
        }
    }

    public function registerMigration($file, $version)
    {

        $this->migrationReflection->registerMigration(["migration" => $file, "version" => $version]);
    }

    public function requireOnce($filepath)
    {
        return require_once $filepath;
    }

    public function migrationFlagHandler($flag)
    {
        if ($flag != null) {
            $flag_action = $this->db_flags[$flag];
            $configuration = $this->db_configurations[$flag_action];
            $this->$configuration();
        }
    }

    public function enableWebSocket($flag = null)
    {

        $component_path = __DIR__ . "/../../components/websocket/socket-skeleton.component";
        $manager_path = __DIR__ . "/../../components/websocket/socket.component";

        if (!$this->checkExistent("./socket")) {

            if ($this->readComponent($manager_path)) {

                $this->module = $this->component;

                if ($this->writeModule("./socket")) {

                    # Create Default File
                    if ($this->readComponent($component_path)) {

                        $socket_name = "Chat";

                        if ($flag == "--name") {
                            $socket_name = ucfirst(end($argv));
                        }

                        if (!is_dir("./Sockets")) {
                            mkdir("./Sockets");
                        }

                        $this->module = preg_replace("/\[SocketName\]/", $socket_name, $this->component);
                        $path = "./Sockets/{$socket_name}.php";

                        if (!$this->checkExistent($path)) {
                            if ($this->writeModule($path)) {
                            }
                        }
                    }

                    $this->verbose("Socket has been activated successfully.");
                    return true;
                }
            }
        }

        $this->verbose("Socket has already been activated...");
        return false;
    }

    public function disableWebSocket($flag = null)
    {

        if ($this->checkExistent("./socket")) {
            if (Fs::delete("socket")) {
            }

            $this->verbose("Socket as been deactivated!");
            return true;
        }

        return false;
    }

    public function FileClassName($filename)
    {
        $split = explode("/", $filename);
        $ex = str_replace(".php", "", end($split));

        $classname = $ex;

        return array("class" => $classname, "file" => end($split));
    }

    public function verbose($message = null, $status = null, $breakline = true)
    {

        if ($this->verbose == true) {
            $this->out($message, $status, $breakline);
        }
    }


    static function verboseI($message = null, $status = null, $breakline = true)
    {
        (new self)->verbose($message, $status, $breakline);
    }

    protected function out($text, $color = null, $newLine = true)
    {
        $styles = array(
            'success' => "\033[0;32m%s\033[0m",
            'error' => "\033[31;31m%s\033[0m",
            'info' => "\033[33;33m%s\033[0m"
        );

        $format = '%s';

        if (isset($styles[$color])) {
            $format = $styles[$color];
        }

        if ($newLine) {
            $format .= PHP_EOL;
        }

        printf($format, $text);
    }
}
