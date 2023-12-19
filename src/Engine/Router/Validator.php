<?php

namespace Boiler\Core\Engine\Router;



class Validator
{

    /**
     * validation status
     *
     * @var bool
     */
    public $validation = false;

    /**
     * validations messages
     *
     * @var array
     */
    protected $validation_messages = array();

    /**
     * Clears all previous validations logs
     *  
     * @return void
     * */

    protected function clearValidationLog()
    {
        if (isset($_SESSION["request_validation_message"])) {
            unset($_SESSION["request_validation_message"]);
        }
    }

    /**
     * validates all required fields that are supplied
     *  
     * @param array fields
     * @return bool
     * */
    public function required($fields)
    {
        $this->clearValidationLog();

        foreach ($fields as $key => $rules) {
            if (isset($this->$key)) {
                $props = $this->validationProperties($rules);

                foreach ($props as $prop) {
                    $this->validatePropType($prop, $key);
                }
            } else {
                $this->validationMessage($key, $this->formatKey($key) . " is required!");
                continue;
            }
        }

        if (count($this->validation_messages) > 0) {
            $this->setValidationLog();
            $this->validation = false;
        } else {
            $this->validation = true;
        }

        return $this->validation;
    }


    /**
     * sets new validations logs using validations messages
     *  
     * @return void
     * */
    protected function setValidationLog()
    {
        if (isset($_SESSION["request_validation_message"])) {
            $this->clearValidationLog();
        }

        $_SESSION["request_validation_message"] = $this->validation_messages;
    }

    /**
     * validates field data types
     *  
     * @param string property
     * @param string field
     * 
     * @return void
     * */
    protected function validatePropType($prop, $field)
    {

        if ($this->$field == null || empty($this->$field)) {
            $this->validationMessage($field, $this->formatKey($field) . " cannot be empty!");
            return;
        }

        if ($prop == "integer") {
            if (!filter_var($this->$field, FILTER_VALIDATE_INT)) {
                $this->validationMessage($field, "Only numbers are allowed in " . $this->formatKeyLowercase($field) . " field");
            }
        } else if ($prop == "float") {
            if (!filter_var($this->$field, FILTER_VALIDATE_FLOAT)) {
                $this->validationMessage($field, "Only floating numbers are allowed in " . $this->formatKeyLowercase($field) . " field");
            }
        } else if ($prop == "boolean") {
            if (!filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)) {
                $this->validationMessage($field, "Only boolean values are allowed in " . $this->formatKeyLowercase($field) . " field");
            }
        } else if ($prop == "string") {
            if (gettype($this->$field) != $prop) {
                $this->validationMessage($field, "Invalid characters for field " . $this->formatKeyLowercase($field));
            }
        } else if ($prop == "email") {
            if (!filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                $this->validationMessage($field, "Invalid email address");
            }
        } else if (strpos($prop, ":")) {
            $this->lengthValidation($prop,  $field);
        } else if ($prop == "array") {
            if (!is_array($this->$field)) {
                $this->validationMessage($field, "Invalid data for field {$this->$field}" . $this->formatKeyLowercase($field));
            }
        }
    }


    /**
     * validates data length
     *  
     * @param string property
     * @param string field
     * 
     * @return void
     * */
    protected function lengthValidation($prop, $field)
    {
        $e = explode(":", $prop);
        $operator = $e[0];
        $length = $e[1];

        $operation = strlen((string) $this->$field) . " $operator " . $length;

        $result = true;

        eval("?>" . '<?php $result = ' . $operation . "; ?>");

        if (!$result) {
            $this->validationMessage($field, $this->formatKey($field) . " must be up to $length characters.");
        }
    }

    /**
     * creates validation messages
     *  
     * @param string field
     * @param string message
     * 
     * @return void
     * */
    protected function validationMessage($field, $message)
    {
        $this->validation_messages[$field] = $message;
    }

    /**
     * process validation properties
     *  
     * @param string validations
     * 
     * @return array|mixed
     * */
    protected function validationProperties($rules)
    {

        if (strpos($rules, "|")) {
            $properties = explode("|", $rules);
        } else {
            $properties = array($rules);
        }

        return $properties;
    }

    public function validationErrors()
    {

        return $this->validation_messages;
    }

    protected function formatKey($key)
    {

        $key = str_replace('_', ' ', $key);
        return ucfirst($key);
    }

    protected function formatKeyLowercase($key)
    {

        return strtolower($this->formatKey($key));
    }
}
