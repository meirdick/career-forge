<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('applications', ApplicationController::class);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.update-status');
    Route::put('applications/{application}/cover-letter', [ApplicationController::class, 'updateCoverLetter'])->name('applications.update-cover-letter');
    Route::post('applications/{application}/generate-cover-letter', [ApplicationController::class, 'generateCoverLetter'])->middleware('ai.access:cover_letter')->name('applications.generate-cover-letter');
    Route::post('applications/{application}/generate-email', [ApplicationController::class, 'generateEmail'])->middleware('ai.access:cover_letter')->name('applications.generate-email');
    Route::resource('applications.notes', ApplicationNoteController::class)->only(['store', 'update', 'destroy']);
});
