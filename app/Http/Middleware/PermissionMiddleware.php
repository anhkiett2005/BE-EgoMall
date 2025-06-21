<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Super-admin được phép tất cả
        if ($user->role->name === 'super-admin') {
            return $next($request);
        }

        // Lấy danh sách quyền của role hiện tại
        $userPermissions = $user->role->permissions->pluck('name')->toArray();

        // Kiểm tra xem có ít nhất 1 quyền khớp
        $hasPermission = false;
        foreach ($permissions as $requiredPermission) {
            if (in_array($requiredPermission, $userPermissions)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return response()->json(['message' => 'Forbidden: Permission denied.'], 403);
        }

        return $next($request);
    }
}