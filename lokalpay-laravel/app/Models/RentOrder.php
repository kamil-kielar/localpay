<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class RentOrder extends Model
{
    use HasPublicId;
    protected $fillable = ['organization_id', 'obligation_id', 'tenant_user_id', 'provider', 'status', 'amount_cents', 'currency', 'idempotency_key', 'provider_order_id', 'checkout_url', 'checkout_expires_at', 'paid_at', 'metadata'];
    protected function casts(): array { return ['checkout_expires_at' => 'datetime', 'paid_at' => 'datetime', 'metadata' => 'array']; }
    public function obligation() { return $this->belongsTo(Obligation::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
}
