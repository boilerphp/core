<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Middlewares\Session;

class Authentication
{

    static public function user()
    {

        if (Session::get("auth")) {
            $id = Session::get("auth");
            return (new AuthenticableUser($id))->user();
        }

        return null;
    }

    static public function logout()
    {
        Session::end("auth");
        Session::end("app_doors_locks");
        Session::end("request_validation_message");
    }

    static public function login($user)
    {
        Session::set("auth", $user->id);
    }
}
