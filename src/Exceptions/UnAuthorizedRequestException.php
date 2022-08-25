<?php

namespace Boiler\Core\Engine\Exceptions;

use Boiler\Core\Engine\Router\Response;
use Exception;
use Throwable;

class UnAuthorizedRequestException extends Exception
{

    public function __construct(public $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        return $this->ContentExceptionResponse();
    }


    protected function ContentExceptionResponse()
    {
    }


    protected function JsonResponse($response, $status)
    {

        Response::json($response, $status);
    }
}
