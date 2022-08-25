<?php 

namespace Boiler\Core\Exceptions;

use Boiler\Core\Exceptions\Interfaces\ExceptionInterface;
use Throwable;

class DriverNotSupportedException extends \Exception implements ExceptionInterface {


    public function __construct(public $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        return $this->ContentExceptionResponse();
    }


    public function ContentExceptionResponse()
    {
    }


    public function JsonResponse($response, $status)
    {
    }
}