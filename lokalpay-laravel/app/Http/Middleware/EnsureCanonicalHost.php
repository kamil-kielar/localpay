<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($expectedHost && ! hash_equals(strtolower($expectedHost), strtolower($request->getHost()))) {
            abort(400, 'Invalid host.');
        }

        return $next($request);
    }
}
