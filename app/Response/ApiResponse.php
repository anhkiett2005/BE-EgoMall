<?php

namespace App\Response;

use Illuminate\Http\JsonResponse;

class ApiResponse {
    /**
     * @param string $message
     * @param int $code
     * @param array $data
     * @return \Illuminate\Http\JsonResponse new instance of the response
     *
     */
    public static function success($message = 'success', $code = 200, $data = []): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], $code);
    }

    /**
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse new instance of the response error
     *
     */

    public static function error($message = 'some thing went wrong !!!', $code = 500, $errors = []): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code
        ], $code);
    }
}
