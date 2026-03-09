<?php

use App\Http\Controllers\Settings\ApiKeyController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\DataExportController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\UserLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Links
    Route::post('settings/links', [UserLinkController::class, 'store'])->name('user-links.store');
    Route::put('settings/links/{userLink}', [UserLinkController::class, 'update'])->name('user-links.update');
    Route::delete('settings/links/{userLink}', [UserLinkController::class, 'destroy'])->name('user-links.destroy');
    Route::post('settings/links/reorder', [UserLinkController::class, 'reorder'])->name('user-links.reorder');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::get('settings/export', DataExportController::class)->name('data-export');

    // API Keys (BYOK)
    Route::get('settings/api-keys', [ApiKeyController::class, 'show'])->name('api-keys.show');
    Route::post('settings/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('settings/api-keys/{apiKeyId}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // Billing & Credits
    Route::get('settings/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('settings/billing/checkout', [BillingController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('billing.checkout');
    Route::get('settings/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('settings/billing/promo', [BillingController::class, 'redeemPromo'])
        ->middleware('throttle:5,1')
        ->name('billing.redeem-promo');
});
