<?php

namespace App\Services;

use App\Models\Obligation;
use App\Models\RevenuePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObligationPaymentService
{
    public function record(Obligation $obligation, int $amountCents, string $source, ?string $transactionId = null, ?int $userId = null, ?string $note = null): RevenuePayment
    {
        return DB::transaction(function () use ($obligation, $amountCents, $source, $transactionId, $userId, $note): RevenuePayment {
            $locked = Obligation::query()->lockForUpdate()->findOrFail($obligation->id);
            $remaining = $locked->amount_cents - $locked->paid_amount_cents;
            if ($amountCents < 1 || $amountCents > $remaining || $locked->status === 'void') {
                throw ValidationException::withMessages(['amount_cents' => 'Kwota przekracza pozostałą należność lub należność jest nieważna.']);
            }
            $payment = RevenuePayment::query()->create([
                'organization_id' => $locked->organization_id,
                'property_id' => $locked->property_id,
                'obligation_id' => $locked->id,
                'recorded_by' => $userId,
                'amount_cents' => $amountCents,
                'currency' => $locked->currency,
                'source' => $source,
                'provider_transaction_id' => $transactionId,
                'paid_on' => now()->toDateString(),
                'note' => $note,
            ]);
            $paid = $locked->paid_amount_cents + $amountCents;
            $locked->update([
                'paid_amount_cents' => $paid,
                'status' => $paid >= $locked->amount_cents ? 'paid' : 'partial',
                'paid_at' => $paid >= $locked->amount_cents ? now() : null,
            ]);
            return $payment;
        });
    }
}
