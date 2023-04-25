<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Database\Model;
use App\Models\User;

class AuthenticableUser extends Model
{

    protected $table = "users";

    public function user()
    {
        $user = new User();
        $columnName = "id";
        return $user->find($this->$columnName);
    }
}
