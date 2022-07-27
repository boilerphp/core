<?php

namespace Boiler\Core\Database;



class Model extends Relations {

    

    public function __construct()
    {
        $this->useTable();
        parent::__construct();
    }
    
    
}