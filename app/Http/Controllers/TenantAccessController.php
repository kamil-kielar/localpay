<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Membership;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantAccessController extends Controller
{
    public function show(Request $request, TenantInvitation $invitation): View
    {
        abort_unless(hash_equals($invitation->token_hash, hash('sha256', (string) $request->query('token'))), 403);
        abort_if($invitation->revoked_at || $invitation->expires_at->isPast() || $invitation->status !== 'pending', 410);
        return view('auth.accept-invitation', compact('invitation'));
    }

    public function accept(Request $request, TenantInvitation $invitation): RedirectResponse
    {
        abort_unless(hash_equals($invitation->token_hash, hash('sha256', (string) $request->input('token'))), 403);
        abort_if($invitation->revoked_at || $invitation->expires_at->isPast() || $invitation->status !== 'pending', 410);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $user = DB::transaction(function () use ($invitation, $data): User {
            $user = User::query()->where('email', strtolower((string) $invitation->email))->first();
            if ($user && !$user->is_active) abort(403, 'Konto jest zawieszone.');
            if ($user && !Hash::check($data['password'], $user->password)) {
                abort(422, 'Dla istniejącego konta podaj jego aktualne hasło.');
            }
            if (!$user) {
                $user = User::query()->create([
                    'email' => strtolower((string) $invitation->email), 'name' => $data['name'],
                    'password' => $data['password'],
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            Membership::query()->firstOrCreate(
                ['organization_id' => $invitation->organization_id, 'user_id' => $user->id],
                ['role' => 'tenant']
            );
            $invitation->lease()->update(['tenant_user_id' => $user->id, 'status' => 'active']);
            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('tenant.portal');
    }

    public function quick(Request $request, Lease $lease): RedirectResponse
    {
        abort_unless($lease->status === 'active', 403);
        $request->session()->regenerate();
        $request->session()->put('quick_lease_id', $lease->id);
        return redirect()->route('tenant.portal');
    }
}
