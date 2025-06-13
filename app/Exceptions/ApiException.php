<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errors;

    public function __construct($message = 'Something went wrong !!!', $statusCode = 500, $errors = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ], $this->statusCode);
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
