<?php

namespace App\Core\Database;



class Model extends Relations {

    

    public function __construct()
    {
        $this->useTable();
        parent::__construct();
    }
    
    
}