<?php

use App\Http\Middleware\AdminAll;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\AssociateAll;
use App\Http\Middleware\AssociateAuth;
use App\Http\Middleware\AuditorScope;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'prevent-back' => PreventBackHistory::class,
            'admin-auth' => AdminAuth::class,
            'admin-all' => AdminAll::class,
            'super-admin' => SuperAdmin::class,
            'auditor-scope' => AuditorScope::class,
            'associate-auth' => AssociateAuth::class,
            'associate-all' => AssociateAll::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
