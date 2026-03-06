<?php

use App\Http\Controllers\GapAnalysisController;
use App\Http\Controllers\GapClosureChatController;
use App\Http\Controllers\GapResolutionController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\PipelineChatController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('job-postings', JobPostingController::class)->except(['store']);
    Route::post('job-postings', [JobPostingController::class, 'store'])->middleware('ai.access:job_analysis')->name('job-postings.store');
    Route::post('job-postings/quick', [JobPostingController::class, 'quickStore'])->middleware('ai.access:job_analysis')->name('job-postings.quick-store');
    Route::post('job-postings/bulk', [JobPostingController::class, 'bulkStore'])->middleware('ai.access:job_analysis')->name('job-postings.bulk-store');
    Route::post('job-postings/{jobPosting}/reanalyze', [JobPostingController::class, 'reanalyze'])->middleware('ai.access:job_analysis')->name('job-postings.reanalyze');
    Route::put('job-postings/{jobPosting}/profile', [JobPostingController::class, 'updateProfile'])->name('job-postings.update-profile');

    // Gap Analysis
    Route::post('job-postings/{jobPosting}/gap-analysis', [GapAnalysisController::class, 'store'])->middleware('ai.access:gap_analysis')->name('gap-analyses.store');
    Route::get('gap-analyses/{gapAnalysis}', [GapAnalysisController::class, 'show'])->name('gap-analyses.show');
    Route::post('gap-analyses/{gapAnalysis}/reanalyze', [GapAnalysisController::class, 'reanalyze'])->middleware('ai.access:gap_analysis')->name('gap-analyses.reanalyze');
    Route::post('gap-analyses/{gapAnalysis}/chat', [GapClosureChatController::class, 'chat'])->middleware('ai.access:chat_message')->name('gap-analyses.chat');
    Route::post('gap-analyses/{gapAnalysis}/save-entries', [GapClosureChatController::class, 'save'])->name('gap-analyses.save-entries');

    // Gap Resolution
    Route::post('gap-analyses/{gapAnalysis}/resolve/{gapArea}/reframe', [GapResolutionController::class, 'reframe'])->middleware('ai.access:gap_reframe')->name('gap-resolutions.reframe');
    Route::post('gap-analyses/{gapAnalysis}/resolve/{gapArea}/accept-reframe', [GapResolutionController::class, 'acceptReframe'])->name('gap-resolutions.accept-reframe');
    Route::post('gap-analyses/{gapAnalysis}/resolve/{gapArea}/reject-reframe', [GapResolutionController::class, 'rejectReframe'])->name('gap-resolutions.rejectReframe');
    Route::post('gap-analyses/{gapAnalysis}/resolve/{gapArea}/answer', [GapResolutionController::class, 'answer'])->name('gap-resolutions.answer');
    Route::post('gap-analyses/{gapAnalysis}/resolve/{gapArea}/acknowledge', [GapResolutionController::class, 'acknowledge'])->name('gap-resolutions.acknowledge');

    // Pipeline Chat
    Route::post('pipeline-chat/resolve', [PipelineChatController::class, 'resolve'])->name('pipeline-chat.resolve');
    Route::post('pipeline-chat/{chatSession}/chat', [PipelineChatController::class, 'chat'])->middleware('ai.access:chat_message')->name('pipeline-chat.chat');

    // Resumes
    Route::post('gap-analyses/{gapAnalysis}/resume', [ResumeController::class, 'generate'])->middleware('ai.access:resume_generation')->name('resumes.generate');
    Route::get('resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::get('resumes/{resume}', [ResumeController::class, 'show'])->name('resumes.show');
    Route::put('resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::put('resumes/{resume}/sections/{resumeSection}', [ResumeController::class, 'selectVariant'])->name('resumes.select-variant');
    Route::put('resumes/{resume}/variants/{resumeSectionVariant}', [ResumeController::class, 'editVariant'])->name('resumes.edit-variant');
    Route::put('resumes/{resume}/sections/{resumeSection}/toggle', [ResumeController::class, 'toggleSection'])->name('resumes.toggle-section');
    Route::patch('resumes/{resume}/sections/{resumeSection}', [ResumeController::class, 'updateSection'])->name('resumes.update-section');
    Route::delete('resumes/{resume}/sections/{resumeSection}', [ResumeController::class, 'destroySection'])->name('resumes.destroy-section');
    Route::delete('resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');

    // Resume Export
    Route::get('resumes/{resume}/preview', [ResumeExportController::class, 'preview'])->name('resumes.preview');
    Route::get('resumes/{resume}/export/{format}', [ResumeExportController::class, 'export'])->name('resumes.export');
    Route::post('resumes/{resume}/finalize', [ResumeExportController::class, 'finalize'])->name('resumes.finalize');
});
