<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrganizationResourcePolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return app()->bound(Organization::class)
            && app(Organization::class)->memberships()->where('user_id', $user->id)->exists();
    }

    public function view(User $user, Model $resource): bool
    {
        return $this->belongsToCurrentOrganization($user, $resource);
    }

    public function create(User $user): bool
    {
        return $this->hasManagementRole($user);
    }

    public function update(User $user, Model $resource): bool
    {
        return $this->hasManagementRole($user) && $this->belongsToCurrentOrganization($user, $resource);
    }

    public function delete(User $user, Model $resource): bool
    {
        return $this->update($user, $resource);
    }

    private function hasManagementRole(User $user): bool
    {
        if (!app()->bound(Organization::class)) return false;
        return app(Organization::class)->memberships()
            ->where('user_id', $user->id)->whereIn('role', ['owner', 'admin', 'manager'])->exists();
    }

    private function belongsToCurrentOrganization(User $user, Model $resource): bool
    {
        return app()->bound(Organization::class)
            && (int) $resource->organization_id === (int) app(Organization::class)->id
            && app(Organization::class)->memberships()->where('user_id', $user->id)->exists();
    }
}
