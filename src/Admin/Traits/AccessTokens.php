<?php

namespace Boiler\Core\Admin\Traits;

use Boiler\Core\Database\DB;
use Boiler\Core\Database\Schema;
use Boiler\Core\Hashing\Hash;
use Boiler\Core\Middlewares\Session;

trait AccessTokens
{

    protected $_table = 'auth_access_tokens';

    public function createAccessToken($name, $access = [], $token_user_id = null)
    {

        $token_type = get_class($this);
        $token = [
            'name' => $name,
            'token_type' => $token_type,
            'token_id' => $token_user_id ? $token_user_id : $this->id,
            'token' => hash('sha256', $this->genenrateToken(40)),
            'access' => json_encode($access),
            'created_date' => timestamp()
        ];

        (new Schema)->table($this->_table)->create($token);
        $encode = (new Hash)->getEncodedBase($token['token']);
        return $encode;
    }


    public function revokeAllToken()
    {
        (new Schema)->query("DELETE FROM `$this->_table` WHERE token_id = '$this->id'");
    }


    public function revokeLastToken()
    {
        (new Schema)->query("DELETE FROM `$this->_table` WHERE token_id = '$this->id' ORDER BY id DESC LIMIT 1");
    }


    protected function genenrateToken($length = 16)
    {

        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    public function setAccessToken($token)
    {
        Session::set('boiler_in_app_access_token', $token->id);
    }

    public function getAccessToken()
    {
        $token = Session::get('boiler_in_app_access_token');
        return DB::table($this->_table)->find($token);
    }

    public function getTokenAccess(): array
    {
        $token = Session::get('boiler_in_app_access_token');
        $access_token = DB::table($this->_table)->find($token);
        if ($access_token) {
            return json_decode($access_token->access, true);
        }

        return [];
    }
}
