<?php

namespace App\Exceptions;

use App\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class TokenBlacklistedException extends \Exception
{
    public function __construct(string $message = "Token không hợp lệ", int $code = 401, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->getCode());
    }
}
