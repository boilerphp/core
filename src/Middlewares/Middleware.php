<?php


namespace Boiler\Core\Middlewares;

use Boiler\Core\Engine\Router\Request;

abstract class Middleware
{

    public $message;

    public $status = 200;

    abstract public function handle(Request $request, $next);
}
