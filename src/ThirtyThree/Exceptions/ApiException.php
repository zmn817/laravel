<?php

namespace ThirtyThree\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $response = null;

    public function __construct($message, $code = 500, $response = null, Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
