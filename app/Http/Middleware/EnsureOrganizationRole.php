<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, 500);
        $role = $organization->memberships()->where('user_id', $request->user()->id)->value('role');
        abort_unless(in_array($role, $roles, true), 403);
        $request->attributes->set('organization_role', $role);
        return $next($request);
    }
}
