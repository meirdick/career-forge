<?php

use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('career-chat', [ChatSessionController::class, 'index'])->name('career-chat.index');
    Route::post('career-chat', [ChatSessionController::class, 'store'])->name('career-chat.store');
    Route::get('career-chat/{chatSession}', [ChatSessionController::class, 'show'])->name('career-chat.show');
    Route::post('career-chat/{chatSession}/chat', [ChatSessionController::class, 'chat'])->name('career-chat.chat');
    Route::post('career-chat/{chatSession}/extract', [ChatSessionController::class, 'extract'])->name('career-chat.extract');
    Route::post('career-chat/{chatSession}/commit', [ChatSessionController::class, 'commit'])->name('career-chat.commit');
    Route::patch('career-chat/{chatSession}', [ChatSessionController::class, 'update'])->name('career-chat.update');
});
