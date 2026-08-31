<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View { return view('auth.verify-email'); }
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->user()->markEmailAsVerified();
            event(new Verified($request->user()));
        }
        $configured = config('lokalpay.super_admin_email');
        if ($configured !== '' && hash_equals($configured, strtolower($request->user()->email))) {
            $request->user()->forceFill(['is_super_admin' => true])->save();
        }
        $selectedPlan = (string) $request->session()->pull('selected_plan', 'free');
        return redirect()->route('dashboard', $selectedPlan !== 'free' ? ['plan' => $selectedPlan] : [])
            ->with('status', 'Adres e-mail został zweryfikowany.');
    }
    public function resend(Request $request): RedirectResponse
    {
        if (!$request->user()->hasVerifiedEmail()) $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Link weryfikacyjny został wysłany.');
    }
}
