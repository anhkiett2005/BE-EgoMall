<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function() {
            // Load thêm config route

            // === Partner route === //
            Route::prefix('partner')
                 ->group(base_path('routes/partner.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Nếu muốn thêm middleware toàn cục, dùng $middleware->push(...)
        // JWT middleware sẽ được gọi trực tiếp trong routes/api.php thông qua class name
        $middleware->alias([
            'role'       => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'inject.api.auth.header' => \App\Http\Middleware\JwtCookieAuth::class,
            'api.auth.check' => \App\Http\Middleware\ApiAuthMiddleware::class,
            'check.token.upload' => \App\Http\Middleware\ApiUploadMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Cấu hình xử lý ngoại lệ
    })->create();
