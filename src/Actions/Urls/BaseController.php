<?php

namespace Boiler\Core\Actions\Urls;

use Boiler\Core\Admin\Auth;
use Boiler\Core\Engine\Router\Response;

class BaseController
{

	public function load()
	{
	}

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

	public function json($data, $status)
	{
		return Response::json($data, $status);
	}

	public function render($view, $content, $status)
	{
		return Response::view($view, $content, $status);
	}

	public function content($text, $status)
	{
		return Response::content($text, $status);
	}

	public function redirectTo($path)
	{
		return Response::redirect($path);
	}

	public function redirectToUrl($url)
	{
		return Response::redirectToHost($url);
	}
}
