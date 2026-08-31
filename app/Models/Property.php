<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;
    protected $fillable = ['name', 'address', 'city', 'postal_code', 'purchase_cost_cents', 'notes'];
    protected $hidden = ['organization_id'];
    public function organization() { return $this->belongsTo(Organization::class); }
    public function leases() { return $this->hasMany(Lease::class); }
    public function revenues() { return $this->hasMany(RevenuePayment::class); }
}
