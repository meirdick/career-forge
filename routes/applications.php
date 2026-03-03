<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('applications', ApplicationController::class);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.update-status');
    Route::resource('applications.notes', ApplicationNoteController::class)->only(['store', 'update', 'destroy']);
});
