<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaseRequest;
use App\Http\Resources\LeaseResource;
use App\Models\Lease;
use App\Models\Organization;
use App\Models\Property;
use App\Models\TenantInvitation;
use App\Notifications\TenantInvitationNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LeaseController extends Controller
{
    public function store(LeaseRequest $request, Organization $organization, AuditService $audit): LeaseResource
    {
        $this->authorize('create', Lease::class);
        $data = $request->validated();
        $property = Property::query()->where('organization_id', $organization->id)->where('public_id', $data['property_id'])->firstOrFail();
        unset($data['property_id']);
        $lease = DB::transaction(function () use ($organization, $data, $property): Lease {
            $lease = $organization->leases()->create($data + ['property_id' => $property->id]);
            $lease->schedule()->create([
                'organization_id' => $organization->id, 'amount_cents' => $lease->monthly_rent_cents,
                'due_day' => $lease->due_day, 'starts_at' => $lease->starts_at, 'ends_at' => $lease->ends_at,
                'active' => $lease->status === 'active',
            ]);
            return $lease;
        });
        $audit->record('lease.created', $lease, null, $lease->toArray());
        return new LeaseResource($lease->load('property'));
    }

    public function update(LeaseRequest $request, Organization $organization, Lease $lease, AuditService $audit): LeaseResource
    {
        abort_unless($lease->organization_id === $organization->id, 404);
        $this->authorize('update', $lease);
        $data = $request->validated();
        $property = Property::query()->where('organization_id', $organization->id)->where('public_id', $data['property_id'])->firstOrFail();
        unset($data['property_id']);
        $before = $lease->toArray();
        $lease->update($data + ['property_id' => $property->id]);
        $lease->schedule()->updateOrCreate(['lease_id' => $lease->id], [
            'organization_id' => $organization->id, 'amount_cents' => $lease->monthly_rent_cents,
            'due_day' => $lease->due_day, 'starts_at' => $lease->starts_at, 'ends_at' => $lease->ends_at,
            'active' => $lease->status === 'active',
        ]);
        $audit->record('lease.updated', $lease, $before, $lease->fresh()->toArray());
        return new LeaseResource($lease->load('property'));
    }

    public function invite(Organization $organization, Lease $lease, AuditService $audit): JsonResponse
    {
        abort_unless($lease->organization_id === $organization->id, 404);
        $this->authorize('update', $lease);
        abort_unless($lease->tenant_email, 422, 'Zaproszenia e-mail wymagają adresu e-mail. Telefon jest wyłącznie metadanym.');
        $result = DB::transaction(function () use ($organization, $lease): array {
            TenantInvitation::query()->where('lease_id', $lease->id)->where('status', 'pending')->update(['status' => 'revoked', 'revoked_at' => now()]);
            $token = Str::random(64);
            $invitation = TenantInvitation::query()->create([
                'organization_id' => $organization->id, 'lease_id' => $lease->id, 'invited_by' => auth()->id(),
                'email' => $lease->tenant_email, 'phone' => $lease->tenant_phone,
                'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7),
            ]);
            $url = URL::temporarySignedRoute('tenant.invitation.show', now()->addDays(7), ['invitation' => $invitation, 'token' => $token]);
            return compact('invitation', 'url');
        });
        Notification::route('mail', $lease->tenant_email)->notify(new TenantInvitationNotification($lease, $result['url']));
        $audit->record('invitation.sent', $result['invitation']);
        return response()->json(['message' => 'Zaproszenie wysłano e-mailem.', 'expires_at' => $result['invitation']->expires_at]);
    }

    public function revoke(Organization $organization, TenantInvitation $invitation, AuditService $audit): JsonResponse
    {
        abort_unless($invitation->organization_id === $organization->id, 404);
        $invitation->update(['status' => 'revoked', 'revoked_at' => now()]);
        $audit->record('invitation.revoked', $invitation);
        return response()->json(['message' => 'Zaproszenie odwołano.']);
    }

    public function quickLink(Organization $organization, Lease $lease, AuditService $audit): JsonResponse
    {
        abort_unless($lease->organization_id === $organization->id, 404);
        $this->authorize('update', $lease);
        abort_unless($lease->status === 'active', 422, 'Szybki dostęp wymaga aktywnej umowy.');
        $url = URL::temporarySignedRoute('tenant.quick', now()->addDays(90), ['lease' => $lease]);
        $audit->record('tenant.quick_link_created', $lease);
        return response()->json([
            'url' => $url,
            'expires_at' => now()->addDays(90),
            'message' => 'Link należy przekazać najemcy bezpiecznym kanałem. LokalPay nie wysyła SMS.',
        ]);
    }
}
