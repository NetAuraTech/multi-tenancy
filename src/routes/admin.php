<?php

use Illuminate\Support\Facades\Route;
use Netauratech\MultiTenancy\Http\Controllers\Admin\TenantController;

/**
 * Tenants
 */
Route::resource('tenants', TenantController::class)->except(['show']);
Route::post('/tenants/{tenant}/domain', [TenantController::class, 'domain_store'])->name('tenants.domain.store');
Route::delete('/tenants/{tenant}/domain', [TenantController::class, 'domain_destroy'])->name('tenants.domain.destroy');
Route::post('/tenants/{tenant}/maintenance', [TenantController::class, 'toggle_maintenance'])->name('tenants.maintenance');