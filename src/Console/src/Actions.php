<?php

namespace Console\Support;

use Boiler\Core\FileSystem\Fs;
use Console\Support\Helpers\ActionHelpers;


class Actions extends ActionHelpers
{

    protected $path;
    protected $fresh;
    protected $force;
    protected $rollback;
    protected $run_flag;
    protected $hasRollbackSteps;
    protected $hasRollbackTarget;


    /**
     * Create new app using command line manager
     * @param $name
     * Boolean response if app is created
     * */
    public function app($name)
    {
    }

    /**
     * Create Controllers using command line manager
     * @param $name, $type
     * Boolean response if controller is created
     * */
    public function controller($name, $flag = null)
    {
        $this->path = $this->getPath("controller") . $name . ".php";

        if ($this->checkExistent($this->path)) {
            $this->verbose("$name already exists");
            return false;
        }

        if ($this->configureController($name, $this->path)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "controller", $this->path);
            }

            return true;
        }

        print("Unable to create controller " . $name);
        return false;
    }

    public function notification($name, $flag = null)
    {
        if (!is_null($flag)) {
            if ($this->flagChecker("notification", $flag)) {
                $this->run_flag = true;
            } else {
                return $this->run_flag = false;
            }
        }

        $path = $this->getPath("notification") . $name . ".php";

        if ($this->checkExistent($path)) {
            $this->verbose("Notification $name already exists");
            exit;
        }

        if ($this->configureNotification($name, $path)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "notification", $path);
            }

            return true;
        }

        print("Unable to create notification " . $name);
        return false;
    }

    public function model($name, $flag = null)
    {
        if (!is_null($flag)) {
            if ($this->flagChecker("model", $flag)) {
                $this->run_flag = true;
            } else {
                return $this->run_flag = false;
            }
        }

        $path = $this->getPath("model") . $name . ".php";

        if ($this->checkExistent($path)) {
            $this->verbose("Model $name already exists");
            exit;
        }

        if ($this->configureModel($name, $path)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "model", $path);
            }

            return true;
        }

        print("Unable to create model " . $name);
        return false;
    }

    public function socket($name, $flag = null)
    {
        if (!is_null($flag)) {
            if ($this->flagChecker("socket", $flag)) {
                $this->run_flag = true;
            } else {
                return $this->run_flag = false;
            }
        }

        $path = "./Sockets/{$name}.php";

        if ($this->checkExistent($path)) {
            $this->verbose("$name already exists");
            exit;
        }

        if ($this->configureSocket($name, $path)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "socket", $path);
            }

            return true;
        }

        print("Unable to create socket " . $name);
        return false;
    }

    public function migration($name, $flag = null)
    {

        $exploded = explode('_', $name);
        if (end($exploded) === 'table' || end($exploded) === 'tables') {
            array_splice($exploded, count($exploded) - 1, 1);

            $name = implode("_", $exploded);
        }

        $table = $this->tableFormating($name);

        if ($flag == "--alter" || $flag == "--a") {
            $file_name = $table . ".php";
            $component = "migration.alter";
        } else {
            $file_name = $table . "_table.php";
            $component = "migration";
        }

        $this->path = $this->getPath("migration") . time() . "_" . $file_name;
        $this->checkMigrationExistent($file_name);


        if ($this->configureMigration($table, $this->path, $component)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "migration", $this->path);
            }

            return true;
        }

        print("Unable to create migration " . $name);
        return false;
    }

    public function websocket($state, $flag = null)
    {

        if ($state == true) {
            $this->enableWebSocket($flag);
        } else {
            $this->disableWebSocket($flag);
        }
    }

    public function seeder($name, $flag = null)
    {

        if (!is_null($flag)) {
            if ($this->flagChecker("seeder", $flag)) {
                $this->run_flag = true;
            } else {
                return $this->run_flag = false;
            }
        }

        $path = $this->getPath("seeder") . $name . ".php";

        if ($this->checkExistent($path)) {
            $this->verbose("Seeder $name already exists");
            exit;
        }

        if ($this->configureSeeder($name, $path)) {

            if (isset($this->run_flag) && $this->run_flag) {
                $this->flagHandler($name, $flag, "seeder", $path);
            }

            return true;
        }

        print("Unable to create seed file " . $name);
        return false;
    }

    public function seed($flags)
    {
        $classes = null;

        $this->pathHandler($flags, "seeder");

        $hasClass = preg_match('/--class=(.*) (.*)/', implode(' ', $flags));
        $hasClass1 = preg_match('/--class=(.*)/', implode(' ', $flags));
        if ($hasClass) {
            $classes = preg_replace("/(.*)--class=(.*) (.*)/", '$2',  implode(' ', $flags));
        } else if ($hasClass1) {
            $classes = preg_replace("/(.*)--class=(.*)/", '$2',  implode(' ', $flags));
        }


        $all_seeder_file = glob($this->getPath('seeder') . "*.php");

        if ($all_seeder_file) {
            foreach ($all_seeder_file as $seeder_file) {
                $this->requireOnce($seeder_file);
            }
        }

        if ($classes !== null) {
            $seeders = explode('|', $classes);
            foreach ($seeders as $seeder) {

                $name = $seeder;
                $seed_file = $this->getPath('seeder') . "{$name}.php";

                $this->verbose("Seeding: ", "info", false);
                $this->verbose("{$seed_file}", breakline: true);

                $this->requireOnce($seed_file);
                $class = new ($this->FileClassName($seed_file)['class']);
                $class->run();

                $this->verbose("Seeded: ", "success", false);
                $this->verbose("{$seed_file}", breakline: true);
            }
        } else {

            $seed_file = $this->getPath('seeder') . "DatabaseSeeder.php";
            $this->requireOnce($seed_file);

            $className = ($this->FileClassName($seed_file)['class']);

            $this->verbose("Seeding: ", "info", false);
            $this->verbose("{$seed_file}", breakline: true);

            $class = new $className;
            $class->run();

            $this->verbose("Seeded: ", "success", false);
            $this->verbose("{$seed_file}", breakline: true);
        }

        $this->verbose("Database seeding completed!", "success");
    }

    public function test($name, $flag = null)
    {

        $unit_test = false;

        if (!is_null($flag)) {
            if ($flag == "--unit") {
                $unit_test = true;
            }
        }


        $path = $this->getPath("tests") . ($unit_test ? 'Unit/' : 'Integration/') . $name . ".php";

        if ($this->checkExistent($path)) {
            $this->verbose("Test $name already exists");
            exit;
        }

        if ($this->configureTest($name, $path, $unit_test)) {
            return true;
        }

        verbose("Unable to create test file " . $name, "info");
        return false;
    }

    /**
     * Create Middlewares using command line manager
     * @param $name
     * @return bool
     * */
    public function middleware($name)
    {
        if (!Fs::is_active_directory($this->getPath("middleware"))) {
            Fs::create_directory($this->getPath("middleware"));
        }

        $this->path = $this->getPath("middleware") . $name . ".php";

        if ($this->checkExistent($this->path)) {
            $this->verbose("$name already exists");
            return false;
        }

        if ($this->configureMiddleware($name, $this->path)) {
            return true;
        }

        print("Unable to create middleware " . $name);
        return false;
    }

    /**
     * Create Jobs using command line manager
     * @param $name
     * @return bool
     * */
    public function job($name)
    {
        if (!Fs::is_active_directory($this->getPath("job"))) {
            Fs::create_directory($this->getPath("job"));
        }

        $this->path = $this->getPath("job") . $name . ".php";

        if ($this->checkExistent($this->path)) {
            $this->verbose("$name already exists");
            return false;
        }

        if ($this->configureJob($name, $this->path)) {
            return true;
        }

        print("Unable to create job " . $name);
        return false;
    }
}
