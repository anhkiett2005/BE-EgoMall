<?php

namespace App\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection as Collection;

class ApiResponse {
    /**
     * @param string $message
     * @param int $code
     * @param array $data
     * @return \Illuminate\Http\JsonResponse new instance of the response
     *
     */
    public static function success($message = 'success', $code = 200, array | Collection $data = []): JsonResponse {
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

    /**
     * Build response for pagination
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function paginate(LengthAwarePaginator $paginator, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(), // dữ liệu trang hiện tại
            'links'   => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'code'    => $code
        ], $code);
    }

    /**
     * Paginate a collection or array
     *
     * @param array|Collection $items
     * @param int $perPage
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function collectionPaginate(array|Collection $items, int $perPage = 15, string $message = 'success', int $code = 200): JsonResponse
    {
        if ($items instanceof Collection === false) {
            $items = collect($items);
        }

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $total       = $items->count();

        $results = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return self::paginate($paginator, $message, $code);
    }
}
