<?php

namespace App\Traits;

use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

trait FormRequestResponseTrait
{
    /**
     * Trả về response JSON lỗi validation chung.
     */

     protected function validationErrorResponse(array $errors, string $message = 'Validation errors', int $status = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'code' => $status,
            'errors' => $errors,
        ], $status));
    }
}
