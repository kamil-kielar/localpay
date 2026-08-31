<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['organization_id', 'plan_id', 'provider', 'provider_customer_id', 'provider_subscription_id', 'status', 'current_period_end', 'last_event_created_at', 'cancel_at_period_end'];
    protected function casts(): array { return ['current_period_end' => 'datetime', 'cancel_at_period_end' => 'boolean']; }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}
