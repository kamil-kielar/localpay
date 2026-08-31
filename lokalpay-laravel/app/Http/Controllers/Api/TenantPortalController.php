<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaseResource;
use App\Http\Resources\ObligationResource;
use App\Models\Lease;
use App\Models\Obligation;
use App\Services\Payments\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantPortalController extends Controller
{
    public function state(Request $request): JsonResponse
    {
        $leases = $this->leases($request)->with('property')->get();
        $ids = $leases->pluck('id');
        $obligations = Obligation::query()->whereIn('lease_id', $ids)->with(['lease', 'property'])->orderBy('due_date')->get();
        return response()->json([
            'leases' => LeaseResource::collection($leases),
            'obligations' => ObligationResource::collection($obligations),
            'payments' => \App\Models\RevenuePayment::query()->whereIn('obligation_id', $obligations->pluck('id'))->latest('paid_on')->get(),
            'notifications' => $request->user()?->notifications()->latest()->limit(50)->get() ?? [],
        ]);
    }

    public function checkout(Request $request, Obligation $obligation, CheckoutService $checkout): JsonResponse
    {
        $request->validate(['provider' => ['required', 'in:stripe,payu']]);
        $leaseIds = $this->leases($request)->pluck('id');
        abort_unless($leaseIds->contains($obligation->lease_id), 404);
        $order = $checkout->rent($obligation, $request->string('provider')->toString(), $request->user()?->id, $request->ip());
        return response()->json(['checkout_url' => $order->checkout_url]);
    }

    public function readNotifications(Request $request): JsonResponse
    {
        $request->user()?->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Powiadomienia oznaczono jako przeczytane.']);
    }

    private function leases(Request $request)
    {
        $id = $request->session()->get('quick_lease_id');
        if ($id) return Lease::query()->whereKey($id)->where('status', 'active');
        return Lease::query()->where('tenant_user_id', $request->user()?->id)->where('status', 'active');
    }
}
