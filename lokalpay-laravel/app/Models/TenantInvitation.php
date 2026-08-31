<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class TenantInvitation extends Model
{
    use HasPublicId;
    protected $fillable = ['organization_id', 'lease_id', 'invited_by', 'email', 'phone', 'token_hash', 'status', 'expires_at', 'accepted_at', 'revoked_at'];
    protected $hidden = ['token_hash'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function lease() { return $this->belongsTo(Lease::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
}
