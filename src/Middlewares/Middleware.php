<?php


namespace Boiler\Core\Middlewares;


abstract class Middleware
{

    public $message;

    public $status = 200;

    abstract public function handle($request, $next);
}
