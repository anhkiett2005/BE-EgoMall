<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permissionName): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Nếu là super-admin => luôn được phép
        if ($user->role->name === 'super-admin') {
            return $next($request);
        }

        // Nếu role không có permission cần thiết
        if (!$user->role->permissions->contains('name', $permissionName)) {
            return response()->json(['message' => 'Forbidden: Permission denied.'], 403);
        }

        return $next($request);
    }
}
