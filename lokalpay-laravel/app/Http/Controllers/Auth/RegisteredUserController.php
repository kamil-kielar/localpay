<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $selectedPlan = in_array($request->query('plan'), ['free', 'growth', 'pro'], true)
            ? (string) $request->query('plan')
            : 'free';
        return view('auth.register', compact('selectedPlan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'selected_plan' => ['required', 'in:free,growth,pro'],
        ]);
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'], 'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null, 'password' => $data['password'],
            ]);
            $free = Plan::query()->where('code', 'free')->firstOrFail();
            $base = Str::slug($data['organization_name']) ?: 'organizacja';
            $organization = Organization::query()->create([
                'owner_id' => $user->id, 'plan_id' => $free->id,
                'name' => $data['organization_name'], 'slug' => $base.'-'.Str::lower(Str::random(6)),
                'billing_email' => strtolower($data['email']),
            ]);
            Membership::query()->create(['organization_id' => $organization->id, 'user_id' => $user->id, 'role' => 'owner']);
            return $user;
        });
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('selected_plan', $data['selected_plan']);
        return redirect()->route('verification.notice');
    }
}
