<?php

use Illuminate\Support\Facades\Route;
use TechGhor\BillingClient\Http\Controllers\BillingController;
use TechGhor\BillingClient\Http\Controllers\SuspendedController;

Route::middleware(['web', 'throttle:30,1'])
    ->prefix('billing')
    ->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('billing.index');
        Route::get('/suspended', [SuspendedController::class, 'show'])->name('billing.suspended');
        Route::post('/refresh', [SuspendedController::class, 'refresh'])->name('billing.refresh');
    });
