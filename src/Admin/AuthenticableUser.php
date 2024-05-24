<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Database\Model;
use App\Models\User;
use Boiler\Core\Middlewares\Session;


class AuthenticableUser extends Model
{

    public $id; 
    
    public function user()
    {
        $auth = json_decode(Session::get("auth"));
        $authenticable = new $auth->class;

        if ($authenticable instanceof AuthenticableUser) {
            return $authenticable->find($auth->id);
        }

        throw new \Exception("Auth must be an instace of AuthenticableUser", 1);
    }
}
