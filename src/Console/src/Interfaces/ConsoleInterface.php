<?php

namespace Console\Support\Interfaces;

interface ConsoleInterface {

    /**
     * checks and returns command length
     * @param array $command 
     * 
     * @return int
     */

    public function getCommandLength(array $command);
}