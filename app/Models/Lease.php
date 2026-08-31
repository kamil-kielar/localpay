<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use HasPublicId, SoftDeletes;
    protected $fillable = ['organization_id', 'property_id', 'tenant_user_id', 'tenant_name', 'tenant_email', 'tenant_phone', 'starts_at', 'ends_at', 'monthly_rent_cents', 'due_day', 'status'];
    protected function casts(): array { return ['starts_at' => 'date', 'ends_at' => 'date']; }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function tenant() { return $this->belongsTo(User::class, 'tenant_user_id'); }
    public function obligations() { return $this->hasMany(Obligation::class); }
    public function invitations() { return $this->hasMany(TenantInvitation::class); }
    public function schedule() { return $this->hasOne(RentSchedule::class); }
}
