<?php

use Illuminate\Support\Facades\Route;
use TechGhor\BillingClient\Http\Controllers\SuspendedController;

Route::middleware(['web', 'throttle:20,1'])
    ->prefix('billing')
    ->group(function () {
        Route::get('/suspended', [SuspendedController::class, 'show'])->name('billing.suspended');
        Route::post('/refresh', [SuspendedController::class, 'refresh'])->name('billing.refresh');
    });
