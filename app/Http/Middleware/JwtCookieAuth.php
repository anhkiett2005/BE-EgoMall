<?php

namespace App\Http\Middleware;

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
        $token = $request->cookie('token');
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        } else {
            return ApiResponse::error('Không thể xác thực!!', Response::HTTP_UNAUTHORIZED);
        }
        return $next($request);
    }
}
