<?php 

namespace Boiler\Core\Exceptions\Interfaces;

interface ExceptionInterface {


    public function ContentExceptionResponse();


    public function JsonResponse($response, $status);

}
