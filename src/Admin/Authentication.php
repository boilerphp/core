<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Middlewares\Session;

class Authentication
{

    static public function user()
    {
        if (Session::get("auth")) {
            return (new AuthenticableUser)->user();
        }

        return null;
    }

    static public function logout()
    {
        Session::end("auth");
        Session::end("app_doors_locks");
        Session::end("request_validation_message");
    }

    static public function login($authenticable)
    {
        if ($authenticable instanceof AuthenticableUser) {
            return Session::set("auth", json_encode(['class' => get_class($authenticable), 'id' => $authenticable->id]));
        }

        throw new \Exception("Auth must be an instace of AuthenticableUser", 1);
    }
}
