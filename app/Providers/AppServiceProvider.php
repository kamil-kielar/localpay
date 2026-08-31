<?php

namespace App\Providers;

use App\Models\Lease;
use App\Models\Obligation;
use App\Models\Property;
use App\Policies\OrganizationResourcePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\Payments\StripeGateway::class);
        $this->app->bind(\App\Services\Payments\PayUGateway::class);
    }

    public function boot(): void
    {
        URL::forceRootUrl((string) config('app.url'));
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Property::class, OrganizationResourcePolicy::class);
        Gate::policy(Lease::class, OrganizationResourcePolicy::class);
        Gate::policy(Obligation::class, OrganizationResourcePolicy::class);
        Gate::define('super-admin', fn ($user): bool => $user->is_super_admin && $user->is_active);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('registration', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by((string) optional($request->user())->id.'|'.$request->ip()));
    }
}
