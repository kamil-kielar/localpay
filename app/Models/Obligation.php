<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obligation extends Model
{
    use HasPublicId, SoftDeletes;
    protected $fillable = ['organization_id', 'property_id', 'lease_id', 'period', 'due_date', 'amount_cents', 'paid_amount_cents', 'currency', 'status', 'paid_at', 'notes'];
    protected $hidden = ['organization_id'];
    protected function casts(): array { return ['due_date' => 'date', 'paid_at' => 'datetime']; }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function lease() { return $this->belongsTo(Lease::class); }
    public function orders() { return $this->hasMany(RentOrder::class); }
}
