<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObligationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'lease_id' => $this->lease?->public_id,
            'property_name' => $this->property?->name, 'tenant_name' => $this->lease?->tenant_name,
            'period' => $this->period, 'due_date' => $this->due_date?->toDateString(),
            'amount_cents' => $this->amount_cents, 'paid_amount_cents' => $this->paid_amount_cents,
            'remaining_cents' => max(0, $this->amount_cents - $this->paid_amount_cents),
            'currency' => $this->currency, 'status' => $this->status,
        ];
    }
}
