<?php

use Boiler\Core\Admin\Door;
use Boiler\Core\Admin\Auth;

if (!function_exists("auth")) {
    /** 
     * 
     * @return Auth::user|null
     */
    function auth()
    {

        $auth = Auth::user();
        if ($auth != null) {
            return $auth;
        }

        return null;
    }
}

if (!function_exists("access")) {
    /** 
     * gives access with permission
     * 
     * @param string $lock
     * @return bool
     */

    function access($lock)
    {
        return Door::hasLock($lock);
    }
}
