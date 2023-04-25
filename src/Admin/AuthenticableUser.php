<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Database\Model;
use App\Models\User;
use Session;

class AuthenticableUser extends Model
{

    protected $table = "users";

    public function user()
    {
        $id = Session::get("auth");
        return (new User)->find($id);
    }
}
