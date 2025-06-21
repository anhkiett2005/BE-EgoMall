<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roleName): Response
    {
        $user = $request->user();

        if (!$user || $user->role->name !== $roleName) {
            return response()->json(['message' => 'Unauthorized: Role not allowed.'], 403);
        }

        return $next($request);
    }
}