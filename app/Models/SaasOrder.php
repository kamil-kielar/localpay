<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class SaasOrder extends Model
{
    use HasPublicId;
    protected $fillable = ['organization_id', 'plan_id', 'provider', 'kind', 'status', 'amount_cents', 'currency', 'idempotency_key', 'provider_order_id', 'provider_subscription_id', 'checkout_url', 'expires_at', 'paid_at', 'metadata'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'paid_at' => 'datetime', 'metadata' => 'array']; }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
}
