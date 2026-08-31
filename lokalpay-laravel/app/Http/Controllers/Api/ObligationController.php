<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ObligationRequest;
use App\Http\Requests\OfflinePaymentRequest;
use App\Http\Resources\ObligationResource;
use App\Models\Lease;
use App\Models\Obligation;
use App\Models\Organization;
use App\Notifications\PaymentReceiptNotification;
use App\Services\AuditService;
use App\Services\ObligationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ObligationController extends Controller
{
    public function store(ObligationRequest $request, Organization $organization, AuditService $audit): ObligationResource
    {
        $this->authorize('create', Obligation::class);
        $data = $request->validated();
        $lease = Lease::query()->where('organization_id', $organization->id)->where('public_id', $data['lease_id'])->firstOrFail();
        unset($data['lease_id']);
        $obligation = $organization->obligations()->create($data + ['lease_id' => $lease->id, 'property_id' => $lease->property_id, 'currency' => 'PLN']);
        $audit->record('obligation.created', $obligation, null, $obligation->toArray());
        return new ObligationResource($obligation->load(['lease', 'property']));
    }

    public function generate(Organization $organization, Lease $lease): JsonResponse
    {
        abort_unless($lease->organization_id === $organization->id, 404);
        $this->authorize('update', $lease);
        $schedule = $lease->schedule()->firstOrCreate([], [
            'organization_id' => $organization->id, 'amount_cents' => $lease->monthly_rent_cents,
            'due_day' => $lease->due_day, 'starts_at' => $lease->starts_at, 'ends_at' => $lease->ends_at,
            'active' => true,
        ]);
        abort_unless($schedule->active, 422, 'Harmonogram jest nieaktywny.');
        $end = $schedule->ends_at ?? now()->addYear();
        $month = $schedule->starts_at->copy()->startOfMonth();
        $count = 0;
        while ($month->lte($end) && $count < 240) {
            $due = Carbon::create($month->year, $month->month, min($schedule->due_day, 28));
            Obligation::query()->firstOrCreate(
                ['lease_id' => $lease->id, 'period' => $month->format('Y-m')],
                ['organization_id' => $organization->id, 'property_id' => $lease->property_id, 'due_date' => $due, 'amount_cents' => $schedule->amount_cents, 'currency' => 'PLN']
            );
            $month->addMonth();
            $count++;
        }
        return response()->json(['message' => 'Harmonogram należności został uzupełniony.', 'months_processed' => $count]);
    }

    public function offline(OfflinePaymentRequest $request, Organization $organization, Obligation $obligation, ObligationPaymentService $payments, AuditService $audit): JsonResponse
    {
        abort_unless($obligation->organization_id === $organization->id, 404);
        $this->authorize('update', $obligation);
        $payment = $payments->record($obligation, $request->integer('amount_cents'), 'offline', null, auth()->id(), $request->string('note')->toString());
        $payment->update(['paid_on' => $request->date('paid_on')]);
        $audit->record('payment.offline_recorded', $payment, null, $payment->toArray());
        if ($obligation->lease->tenant) $obligation->lease->tenant->notify(new PaymentReceiptNotification($obligation->fresh()));
        $organization->owner->notify(new PaymentReceiptNotification($obligation->fresh()));
        return response()->json(['message' => 'Wpłata została zapisana.']);
    }

    public function void(Organization $organization, Obligation $obligation): JsonResponse
    {
        abort_unless($obligation->organization_id === $organization->id, 404);
        $this->authorize('update', $obligation);
        abort_if($obligation->paid_amount_cents > 0, 422, 'Należność z wpłatami nie może zostać unieważniona.');
        $obligation->update(['status' => 'void']);
        return response()->json(['message' => 'Należność unieważniono.']);
    }
}
