<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'property_id' => $this->property?->public_id,
            'property_name' => $this->property?->name, 'tenant_name' => $this->tenant_name,
            'tenant_email' => $this->tenant_email, 'tenant_phone' => $this->tenant_phone,
            'starts_at' => $this->starts_at?->toDateString(), 'ends_at' => $this->ends_at?->toDateString(),
            'monthly_rent_cents' => $this->monthly_rent_cents, 'due_day' => $this->due_day, 'status' => $this->status,
        ];
    }
}
