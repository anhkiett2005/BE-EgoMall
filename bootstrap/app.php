<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Nếu muốn thêm middleware toàn cục, dùng $middleware->push(...)
        // JWT middleware sẽ được gọi trực tiếp trong routes/api.php thông qua class name
        $middleware->alias([
        'role'       => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Cấu hình xử lý ngoại lệ
    })->create();
