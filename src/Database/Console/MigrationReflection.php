<?php 

namespace Boiler\Core\Database\Console;

use Boiler\Core\Database\Migration\Diagram;
use Boiler\Core\Database\Migration\Migration;
use Boiler\Core\Database\Migration\Table;

class MigrationReflection extends Migration {


    protected $table = "migrations";


    public function __construct(public $verbose = true)
    {
        parent::__construct();
        $this->init();
    }

    public function init() {

        Table::create("migrations", function(Diagram $diagram) {
            $diagram->id();
            $diagram->column("migration")->string();
            $diagram->column("version")->integer();
            $diagram->timestamps();
        });
    }


    public function checkMigration($migration) {

        $checker = $this->query("SELECT migration FROM migrations WHERE migration = '$migration'");
        if($checker->rowCount() > 0) {
            return true;
        }

        return false;
    }


    public function migrationClass($migration)
    {
        $class = $this->mFileFormater($migration)["class"];
        return new $class;
    }


    public function migrationName($migration)
    {
        $name = $this->mFileFormater($migration)["file"];
        return $name;
    }


    public function clearTable() {
        $this->truncate();
    }

    public function getTables() {

        if(env('APP_ENV') == 'testing' && env('DB_CONNECTION') == 'sqlite') {
            $tables = $this->query("SELECT name FROM sqlite_schema WHERE type ='table' AND name NOT LIKE 'sqlite_%'");
        } else {
            $tables = $this->query('show tables');
        }

        return $tables->fetchAll();

    }

    public function dropAllTables() {

        $tables = $this->getTables();
        if($tables) 
        {
            foreach($tables as $key => $value) 
            {
                foreach($value as $defination => $name) 
                {
                    if($name === "migrations") {
                        continue;
                    }
                    // Drop
                    Table::dropIfExists($name);
                    Table::dropIfExists($name);

                }
            }

            return count($tables) - 1;
        }

        return 0;
    }


    public function deleteMigration($migration_file) {

        $migration = $this->mFileFormater($migration_file)['file'];
        $this->table("migrations")->delete("migration", $migration);
    }


    public function getCurrentMigrationVersion() {

        $last = $this->last();
        if($last) {
            return $last->version;
        }

        return 0;
    }

    
    public function mFileFormater($migration)
    {
        $split = explode("/", $migration);
        $ex = str_replace(".php", "", end($split));

        $exMfile = explode("_", $ex);
        array_shift($exMfile);

        $classname = "";
        $tablename = "";
        foreach ($exMfile as $piece) {
            $classname .= ucfirst($piece);
            $tablename .= ucfirst($piece) . " ";
        }

        $filename = $ex;

        return array("class" => $classname, "file" => $filename, "table" => $tablename);
    }
}