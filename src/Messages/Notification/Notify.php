<?php

namespace App\Core\Messages\Notification;

use App\Core\Messages\Mail\Mail;


class Notify extends Mail implements NotifyBuilderInterface
{

    public function __construct()
    {
    }

    public function build()
    {
        return $this->build();
    }
}
