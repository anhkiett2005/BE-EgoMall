<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request; // Thêm Request nếu cần
use Symfony\Component\HttpFoundation\Response; // Thêm Response nếu cần

class JwtCookieAuth
{
    /**
     * Đọc token từ cookie 'token' và gắn vào header Authorization
     */
    public function handle($request, Closure $next)
    {
        if (!$request->hasHeader('Authorization')) {
            throw new ApiException('Không thể xác thực!!', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
