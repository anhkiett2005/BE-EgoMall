<?php

namespace App\Http\Middleware;

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
        if ($token = $request->cookie('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        return $next($request);
    }
}
