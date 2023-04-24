<?php

namespace Boiler\Core\Admin;

use Boiler\Core\Database\Model;
use App\Models\User;

class AuthenticableUser extends Model
{

    protected $table = "users";

    public function __construct(public int|null $id = null)
    {
    }

    public function user()
    {
        return (new User)->find($this->id);
    }
}
