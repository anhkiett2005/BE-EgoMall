<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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

            $user = JWTAuth::parseToken()->authenticate();
            
            if(!$user) {
                throw new ApiException('Không thể xác thực!!', Response::HTTP_UNAUTHORIZED);
            }
        } catch (TokenExpiredException $e) {
            throw new ApiException('Phiên đã hết hạn!!', Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            throw new ApiException('Token không hợp lệ!!', Response::HTTP_UNAUTHORIZED);
        }
        return $next($request);
    }
}
