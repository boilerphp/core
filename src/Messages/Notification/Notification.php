<?php

namespace Boiler\Core\Messages\Notification;

use Boiler\Core\Messages\Mail\Mail;

class Notification extends Notify
{

    public function __construct()
    {
    }

    public function send() 
    {
        if($this->build() instanceof Mail) 
        {
            return $this->build()->send();
        }
        else 
        {
            return $this->build();
        }
    }

}
