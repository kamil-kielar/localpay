<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'price_cents', 'property_limit', 'features', 'active'];
    protected function casts(): array { return ['features' => 'array', 'active' => 'boolean']; }
}
