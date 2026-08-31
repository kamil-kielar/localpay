<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class RevenuePayment extends Model
{
    use HasPublicId;
    protected $fillable = ['organization_id', 'property_id', 'obligation_id', 'recorded_by', 'amount_cents', 'currency', 'source', 'provider_transaction_id', 'paid_on', 'note'];
    protected $hidden = ['organization_id'];
    protected function casts(): array { return ['paid_on' => 'date']; }
    public function property() { return $this->belongsTo(Property::class); }
    public function obligation() { return $this->belongsTo(Obligation::class); }
}
