<?php

namespace App\Core\Actions\Urls;

use App\Core\Engine\Router\Response;
use App\Core\Admin\Auth;


class BaseController
{


	public function detectCrossDomain($redirect)
	{
		if (stripos($redirect, "account.") > 0) {
			return true;
		} else {
			return false;
		}
	}


	public function hasAuthAccess($name, $redirect)
	{

		if (isset($_SESSION[$name])) {
			$logger = true;
		} else {
			if (strpos($redirect, "//")) {
				Response::redirectToHost($redirect);
			} else {
				Response::redirect($redirect);
			}
		}
	}

	public function hasPermission($permission, $redirect)
	{

		$permissions = Auth::user()->permissions;

		if (!array_key_exists($permission, $permissions)) {
			return Response::redirect($redirect);
		}
	}

	public function hasPermissions($list, $redirect)
	{

		$permissions = Auth::user()->permissions;

		foreach ($list as $permission) {
			if (!array_key_exists($permission, $permissions)) {
				return Response::redirect($redirect);
			}
		}
	}
}
