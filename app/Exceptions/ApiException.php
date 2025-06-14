<?php

namespace App\Exceptions;

use App\Response\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errors;

    public function __construct($message = 'Something went wrong !!!', $statusCode = 500, $errors = [])
    {
        parent::__construct($message,$statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render($request): JsonResponse
    {
        return ApiResponse::error($this->message, $this->errors, $this->statusCode);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getStatus()
    {
        return $this->statusCode;
    }
}
