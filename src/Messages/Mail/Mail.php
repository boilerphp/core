<?php

namespace Boiler\Core\Messages\Mail;

class Mail extends MailSender
{

    public function __construct()
    {
    }

    public function send()
    {
        return $this->sendMail();
    }

}
