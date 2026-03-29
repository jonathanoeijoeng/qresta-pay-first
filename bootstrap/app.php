<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'order.env' => \App\Http\Middleware\LoadOrderEnvironment::class,
            'xendit' => \App\Http\Middleware\VerifyXenditWebhook::class
        ]);
        // 2. KECUALIKAN ROUTE CALLBACK DARI CSRF
        $middleware->validateCsrfTokens(except: [
            'api/xendit/callback', // Sesuaikan dengan URL yang Anda buat di routes/api.php
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
