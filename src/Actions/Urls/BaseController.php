<?php

namespace Boiler\Core\Actions\Urls;

use Boiler\Core\Admin\Auth;


class BaseController
{


	public function detectCrossDomain($redirect, $domainSuffix = "*")
	{
		if (stripos($redirect, ($domainSuffix === "*" ? "." : $domainSuffix)) > 0) {
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
				redirectToHost($redirect);
			} else {
				redirect($redirect);
			}
		}
	}

	public function hasPermission($permission, $redirect)
	{

		$permissions = Auth::user()->permissions;

		if (!array_key_exists($permission, $permissions)) {
			return redirect($redirect);
		}
	}

	public function hasPermissions($list, $redirect)
	{

		$permissions = Auth::user()->permissions;

		foreach ($list as $permission) {
			if (!array_key_exists($permission, $permissions)) {
				return redirect($redirect);
			}
		}
	}
}
