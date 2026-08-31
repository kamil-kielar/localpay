<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentSchedule extends Model
{
    protected $fillable = ['organization_id', 'lease_id', 'amount_cents', 'due_day', 'starts_at', 'ends_at', 'active'];
    protected function casts(): array { return ['starts_at' => 'date', 'ends_at' => 'date', 'active' => 'boolean']; }
    public function lease() { return $this->belongsTo(Lease::class); }
}
