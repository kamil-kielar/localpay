<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;
    protected $fillable = ['owner_id', 'plan_id', 'current_subscription_id', 'name', 'slug', 'status', 'billing_email', 'plan_expires_at'];
    protected function casts(): array { return ['plan_expires_at' => 'datetime']; }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function memberships() { return $this->hasMany(Membership::class); }
    public function users() { return $this->belongsToMany(User::class, 'memberships')->withPivot('role')->withTimestamps(); }
    public function properties() { return $this->hasMany(Property::class); }
    public function leases() { return $this->hasMany(Lease::class); }
    public function obligations() { return $this->hasMany(Obligation::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function currentSubscription() { return $this->belongsTo(Subscription::class, 'current_subscription_id'); }
}
