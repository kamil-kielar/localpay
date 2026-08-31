<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantAccessController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/rejestracja', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/rejestracja', [RegisteredUserController::class, 'store'])->middleware('throttle:registration');
    Route::get('/logowanie', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/logowanie', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/haslo/przypomnij', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/haslo/email', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/haslo/reset/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/haslo/reset', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/wyloguj', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::middleware('verified')->group(function (): void {
        Route::get('/panel', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/administrator', [DashboardController::class, 'dashboard'])->middleware('can:super-admin')->name('admin.dashboard');
        Route::view('/platnosci/powrot/{status}', 'billing-return')->name('billing.return');
    });
});
Route::get('/portal-najemcy', [DashboardController::class, 'tenant'])
    ->middleware('tenant.access')->name('tenant.portal');
Route::get('/zaproszenie/{invitation}', [TenantAccessController::class, 'show'])->middleware('signed')->name('tenant.invitation.show');
Route::post('/zaproszenie/{invitation}', [TenantAccessController::class, 'accept'])->middleware('signed')->name('tenant.invitation.accept');
Route::get('/dostep/{lease}', [TenantAccessController::class, 'quick'])->middleware('signed')->name('tenant.quick');
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('/webhooks/payu', [WebhookController::class, 'payu'])->name('webhooks.payu');
