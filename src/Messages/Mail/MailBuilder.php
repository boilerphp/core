<?php

namespace Boiler\Core\Messages\Mail;

use App\Config\MailConfig;
use Boiler\Core\Engine\Router\Response;



class MailBuilder extends MailConfig
{

    public $subject;
    public $message;
    public $header;
    public $mime = "1.0";
    public $charset = "UTF-8";
    public $contentType = 'text/html';

    protected $ccs = [];
    protected $bccs = [];
    protected $attachements = [];


    public function from($email, $name = '')
    {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // throw Invalid email address
        }

        $this->from = $email;
        if ($name != '') {
            $this->fromName = $name;
        }

        return $this;
    }


    public function to($email, $name = '')
    {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // throw Invalid email address
        }

        $this->to = $email;
        $this->toName = $name;

        return $this;
    }


    public function addCC($email, $name)
    {
        array_push($this->ccs, ["email" => $email, "name" => $name]);
    }


    public function addBCC($email, $name)
    {
        array_push($this->bccs, ["email" => $email, "name" => $name]);
    }


    public function message($text)
    {

        $this->message .= $text;
        return $this;
    }


    public function paragraph($text)
    {

        $this->message .= "<p>{$text}</p>";
        return $this;
    }

    public function link($title, $path, $style = null)
    {
        $this->message .= "<div><a href='{$path}' target='_blank' class='{$style}'>{$title}</a></div>";
        return $this;
    }


    public function setHeaders($keys, $value = null)
    {
        if (is_array($keys)) {
            foreach ($keys as $key => $value) {
                $this->$key = $value;
            }
        } else {
            $this->$keys = $value;
        }

        return $this;
    }


    public function subject($string)
    {

        $this->subject = $string;
        return $this;
    }

    public function template($data, $view = "")
    {

        if ($view == "") {
            $view = strtolower(get_class());
        }

        $this->message = Response::mailPage($view, $data);
        return $this;
    }

    public function addAttachments(array $attachements)
    {
        foreach ($attachements as $attachement) {
            array_push($this->attachements, $attachement);
        }

        return $this;
    }

    public function addAttachment(string $attachement)
    {
        array_push($this->attachements, $attachement);
        return $this;
    }

    public function getAttachments()
    {
        return $this->attachements;
    }
}
