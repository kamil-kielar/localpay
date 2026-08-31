<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'name' => $this->name, 'address' => $this->address,
            'city' => $this->city, 'postal_code' => $this->postal_code,
            'purchase_cost_cents' => $this->purchase_cost_cents, 'notes' => $this->notes,
            'leases_count' => $this->whenCounted('leases'), 'created_at' => $this->created_at,
        ];
    }
}
