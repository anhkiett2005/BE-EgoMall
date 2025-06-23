<?php

namespace App\Http\Middleware;

use App\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if(!auth('api')->check()) {
                return ApiResponse::error('Không thể xác thực!!', Response::HTTP_UNAUTHORIZED);
            }
        } catch (TokenExpiredException $e) {
            return ApiResponse::error('Phiên đã hết hạn!!', Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            return ApiResponse::error('Token không hợp lệ!!', Response::HTTP_UNAUTHORIZED);
        }
        return $next($request);
    }
}
