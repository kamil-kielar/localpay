<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\ObligationController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\TenantPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenant')->middleware('tenant.access')->group(function (): void {
    Route::get('/state', [TenantPortalController::class, 'state']);
    Route::post('/obligations/{obligation}/checkout', [TenantPortalController::class, 'checkout'])->middleware('throttle:checkout');
    Route::post('/notifications/read', [TenantPortalController::class, 'readNotifications']);
});
Route::middleware(['auth:sanctum', 'active', 'verified'])->group(function (): void {
    Route::middleware('organization')->group(function (): void {
        Route::get('/plans', [BillingController::class, 'plans']);
        Route::middleware('org.role:owner,admin,manager')->group(function (): void {
            Route::get('/dashboard', DashboardApiController::class);
            Route::apiResource('properties', PropertyController::class)->except(['show']);
            Route::post('/leases', [LeaseController::class, 'store']);
            Route::put('/leases/{lease}', [LeaseController::class, 'update']);
            Route::post('/leases/{lease}/invite', [LeaseController::class, 'invite']);
            Route::post('/leases/{lease}/quick-link', [LeaseController::class, 'quickLink']);
            Route::post('/invitations/{invitation}/revoke', [LeaseController::class, 'revoke']);
            Route::post('/obligations', [ObligationController::class, 'store']);
            Route::post('/leases/{lease}/generate-obligations', [ObligationController::class, 'generate']);
            Route::post('/obligations/{obligation}/offline-payment', [ObligationController::class, 'offline']);
            Route::post('/obligations/{obligation}/void', [ObligationController::class, 'void']);
        });
        Route::middleware('org.role:owner,admin')->group(function (): void {
            Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:checkout');
            Route::get('/billing/portal', [BillingController::class, 'portal']);
        });
    });
    Route::prefix('admin')->middleware('can:super-admin')->group(function (): void {
        Route::get('/overview', [AdminController::class, 'overview']);
        Route::patch('/users/{user}', [AdminController::class, 'user']);
        Route::patch('/organizations/{organization}', [AdminController::class, 'organization']);
    });
});
