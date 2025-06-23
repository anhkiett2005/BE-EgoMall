<?php

namespace App\Http\Middleware;

use App\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roleNames): Response
    {
        $user = auth('api')->user();

        if (!$user || !in_array($user->role->name, $roleNames)) {
            return ApiResponse::error('Cấm: Vai trò không có quyền truy cập!!', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
