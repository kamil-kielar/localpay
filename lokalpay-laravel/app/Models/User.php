<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasPublicId, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'password'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_super_admin' => 'boolean', 'is_active' => 'boolean'];
    }
    public function organizations() { return $this->belongsToMany(Organization::class, 'memberships')->withPivot('role')->withTimestamps(); }
    public function memberships() { return $this->hasMany(Membership::class); }
    public function leases() { return $this->hasMany(Lease::class, 'tenant_user_id'); }
}
