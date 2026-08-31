<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);
        $publicId = $request->header('X-Organization-ID') ?: $request->session()->get('organization_public_id');
        $query = Organization::query()->whereHas('memberships', fn ($q) => $q->where('user_id', $user->id));
        $organization = $publicId ? $query->where('public_id', $publicId)->first() : $query->first();
        abort_unless($organization && $organization->status === 'active', 403, 'Brak dostępu do organizacji.');
        $request->session()->put('organization_public_id', $organization->public_id);
        $request->attributes->set('organization', $organization);
        app()->instance(Organization::class, $organization);
        return $next($request);
    }
}
