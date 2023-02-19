<?php

namespace Boiler\Core\Admin\Traits;

use Boiler\Core\Database\Schema;
use Boiler\Core\Hashing\Hash;

trait AccessTokens
{ 
    
    protected $_table = 'auth_access_tokens';


    public function createAccessToken($name, $access = [], $token_user_id = null) {

        $token_type = get_class($this);
        $token = [
            'name' => $name,
            'token_type' => $token_type,
            'token_id' => $token_user_id ? $token_user_id : $this->id,
            'token' => hash('sha256', $this->genenrateToken(40)),
            'access' => json_encode($access)
        ];

        (new Schema)->table($this->_table)->create($token);
        $encode = (new Hash)->getEncodedBase($token['token']);
        return $encode;
    }


    public function revokeAllToken() {
        (new Schema)->table($this->_table)->where('token_id', $this->id)->delete();
    }


    public function revokeLastToken() {
        (new Schema)->table($this->_table)->where('token_id', $this->id)->last()->delete();
    }
    

    protected function genenrateToken($length = 16) {

        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string; 
    }
}
