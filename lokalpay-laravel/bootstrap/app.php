<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureCanonicalHost;
use App\Http\Middleware\EnsureOrganizationRole;
use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\ResolveOrganization;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureCanonicalHost::class, SecurityHeaders::class]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/payu',
        ]);
        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'organization' => ResolveOrganization::class,
            'org.role' => EnsureOrganizationRole::class,
            'tenant.access' => EnsureTenantAccess::class,
        ]);
        $middleware->trustProxies(at: config('lokalpay.trusted_proxies', []), headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PREFIX);
    })->create();
