<?php


namespace Boiler\Core\Console;

use Console\Support\Interfaces\ConsoleInterface;

class Console extends Command implements ConsoleInterface
{

    public function __construct($server, $argv = null, $verbose = true)
    {
        parent::__construct();
        
        $server->start(true);
        $this->arguments = $argv;

        $this->setVerbose($verbose);
    }

    public function run()
    {
        array_splice($this->arguments, 0, 1);
        if ($this->getCommandLength($this->arguments) > 0) {
            $this->parse($this->arguments);
        }
    }

    public function parse($arguments)
    {
        $command = $arguments[0];

        if (in_array($command, $this->commands)) {
            // Remove command from arguments 
            array_splice($arguments, 0, 1);
            // Use function to execute commands
            $this->$command($arguments);
        }
    }

    public function command($command) {
        
        $arguments = explode(' ', $command);
        $this->parse($arguments);
    }

    public function exec($command) {
        
        exec($command);
    }

    /**
     * checks and returns command length
     * @param command 
     * @return int
     */
    public function getCommandLength(array $command)
    {
        return count($command);
    }

    /**
     * set if console should display command outputs
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose) 
    {
        $this->verbose = $verbose;
    }
}
