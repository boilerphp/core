<?php


namespace Boiler\Core\Console;

use Console\Support\Interfaces\ConsoleInterface;

class Console extends Command implements ConsoleInterface
{

    protected $arguments;

    public function __construct($server = null, $argv = null, $verbose = true)
    {
        parent::__construct();

        !is_null($server) ? $server->start(true) : null;
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
            if (strpos($command, ':')) {
                $list = explode(':', $command);
                $command = $list[0];
                for ($i = 1; $i < count($list); $i++) {
                    $command .= ucfirst($list[$i]);
                }
            }

            $this->$command($arguments);
        }
    }

    public function command($command)
    {

        $arguments = explode(' ', $command);
        $this->parse($arguments);
    }

    public function exec($command)
    {
        $output = [];
        $result_code = [];

        exec($command, $output, $result_code);

        foreach ($output as $message) {
            verbose($message);
        }
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
