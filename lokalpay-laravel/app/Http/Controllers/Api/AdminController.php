<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\RentOrder;
use App\Models\SaasOrder;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'kpis' => [
                'users' => User::count(), 'organizations' => Organization::count(),
                'active_subscriptions' => Subscription::where('status', 'active')->count(),
                'paid_saas_cents' => SaasOrder::where('status', 'paid')->sum('amount_cents'),
                'paid_rent_cents' => RentOrder::where('status', 'paid')->sum('amount_cents'),
            ],
            'users' => User::latest()->limit(100)->get(['public_id', 'name', 'email', 'is_active', 'is_super_admin', 'created_at']),
            'organizations' => Organization::with(['plan', 'owner'])->latest()->limit(100)->get(),
            'subscriptions' => Subscription::with(['organization', 'plan'])->latest()->limit(100)->get(),
            'orders' => SaasOrder::with(['organization', 'plan'])->latest()->limit(100)->get(),
            'rent_orders' => RentOrder::with(['organization', 'obligation.property'])->latest()->limit(100)->get(),
            'audit_logs' => AuditLog::latest()->limit(200)->get(),
        ]);
    }

    public function user(Request $request, User $user): JsonResponse
    {
        abort_if($user->is(auth()->user()), 422, 'Nie można zawiesić własnego konta.');
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->forceFill($data)->save();
        if (!$user->is_active) \DB::table('sessions')->where('user_id', $user->id)->delete();
        return response()->json(['message' => 'Status użytkownika zmieniono.']);
    }

    public function organization(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,suspended'], 'plan' => ['required', 'exists:plans,code'], 'plan_expires_at' => ['nullable', 'date']]);
        $plan = Plan::where('code', $data['plan'])->firstOrFail();
        $organization->update(['status' => $data['status'], 'plan_id' => $plan->id, 'plan_expires_at' => $data['plan_expires_at']]);
        return response()->json(['message' => 'Organizacja została zaktualizowana.']);
    }
}
