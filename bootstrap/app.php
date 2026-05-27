<?php

use App\Http\Middleware\AdminAll;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\EnsurePortalAuthenticated;
use App\Http\Middleware\EnsurePortalParent;
use App\Http\Middleware\EnsurePortalStudent;
use App\Http\Middleware\EnsurePortalTeacher;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\PortalRememberFromCookie;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RedirectIfAdminMustChangePassword;
use App\Http\Middleware\SyncPortalSessionUser;
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
            'admin-all' => AdminAll::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
