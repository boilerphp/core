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
        $uniqueColumn = $user->getUniqueColumn();
        return $user->find($this->$uniqueColumn);
    }
}
