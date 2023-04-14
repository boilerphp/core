<?php

namespace Boiler\Core\Database;

class Relations extends Schema {

    protected $name;

    protected $useKey;

    protected $value_key;
    
    protected $foreign_key;

    protected $props;
    
    protected $namespace;
    
    protected $class;
    
    protected $success;


    public function __construct()
    {
        parent::__construct();
    }

    public function createOne($data)
    {
        $data[$this->useKey] = $this->extractValue($this->props, $this->useKey);
        if($this->insert($data))
        {
            $this->success = true;
        }

        return $this;
    }

    public function createMultiple($collection)
    {
        foreach($collection as $data) {
            $this->createOne($data);
        }
    }

    public function hasOne($model, $key = null) 
    {
        if($this->setModelProperties($model)) 
        {
            if($this->setKeys($key)) 
            {
                $class = new $model;
                $value_key = $this->value_key;

                return $class->where($this->foreign_key, $this->$value_key)->attachClass();
            }
        }
    }

    public function hasMultiple($model, $key = null)
    {
        if($this->setModelProperties($model)) 
        {
            if($this->setKeys($key)) 
            {
                $class = new $model;
                $value_key = $this->value_key;
                
                return $class->where($this->foreign_key, $this->$value_key)->attachClass(true);
            }
        }
    }

    protected function extractValue($object, $foreign_key) 
    {
        return $object->$foreign_key;
    }

    protected function getRelationsName() 
    {
        return $this->name;
    }

    protected function setModelProperties($model) 
    {
        if($model) 
        {

            $split_ = explode("\\", strtolower($model));

            $this->namespace = $split_[0];
            $this->class = $split_[1];

            return true;
        }

        return false;
    }

    protected function setKeys($key)
    {

        if(!is_null($key)) 
        {
            $this->value_key = $key;
            $this->foreign_key = $key;

            if (is_array($key)) 
            {
                foreach ($key as $_key => $_value) 
                {
                    $this->foreign_key = $_key;
                    $this->value_key = $_value;
                }

            }
        }
        else 
        {
            $modelClassNameSpace = get_class($this);
            $modelClassName = explode("\\", $modelClassNameSpace);

            $this->foreign_key = strtolower(end($modelClassName))."_id";
            $this->value_key = "id";
        }

        return true;

    }

}