<?php

namespace App\Http\Middleware;

use App\Models\Lease;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active && Lease::query()->where('tenant_user_id', $request->user()->id)->where('status', 'active')->exists()) {
            return $next($request);
        }
        $leaseId = $request->session()->get('quick_lease_id');
        abort_unless($leaseId && Lease::query()->whereKey($leaseId)->where('status', 'active')->exists(), 401, 'Wymagany dostęp najemcy.');
        return $next($request);
    }
}
