<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['organization_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'before', 'after', 'created_at', 'updated_at'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime']; }
}
