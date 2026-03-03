<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransparencyPageController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/experience-library.php';
require __DIR__.'/job-postings.php';
require __DIR__.'/applications.php';

// Transparency - authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('applications/{application}/transparency', [TransparencyPageController::class, 'show'])->name('transparency.show');
    Route::put('applications/{application}/transparency', [TransparencyPageController::class, 'update'])->name('transparency.update');
    Route::post('applications/{application}/transparency/publish', [TransparencyPageController::class, 'publish'])->name('transparency.publish');
});

// Transparency - public page (no auth)
Route::get('t/{transparencyPage:slug}', [TransparencyPageController::class, 'publicPage'])->name('transparency.public');
