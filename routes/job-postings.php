<?php

use App\Http\Controllers\GapAnalysisController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('job-postings', JobPostingController::class);
    Route::post('job-postings/{jobPosting}/reanalyze', [JobPostingController::class, 'reanalyze'])->name('job-postings.reanalyze');

    // Gap Analysis
    Route::post('job-postings/{jobPosting}/gap-analysis', [GapAnalysisController::class, 'store'])->name('gap-analyses.store');
    Route::get('gap-analyses/{gapAnalysis}', [GapAnalysisController::class, 'show'])->name('gap-analyses.show');
    Route::post('gap-analyses/{gapAnalysis}/finalize', [GapAnalysisController::class, 'finalize'])->name('gap-analyses.finalize');

    // Resumes
    Route::post('gap-analyses/{gapAnalysis}/resume', [ResumeController::class, 'generate'])->name('resumes.generate');
    Route::get('resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::get('resumes/{resume}', [ResumeController::class, 'show'])->name('resumes.show');
    Route::put('resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::put('resumes/{resume}/sections/{resumeSection}', [ResumeController::class, 'selectVariant'])->name('resumes.select-variant');
    Route::put('resumes/{resume}/variants/{resumeSectionVariant}', [ResumeController::class, 'editVariant'])->name('resumes.edit-variant');
    Route::delete('resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');

    // Resume Export
    Route::get('resumes/{resume}/preview', [ResumeExportController::class, 'preview'])->name('resumes.preview');
    Route::get('resumes/{resume}/export/{format}', [ResumeExportController::class, 'export'])->name('resumes.export');
    Route::post('resumes/{resume}/finalize', [ResumeExportController::class, 'finalize'])->name('resumes.finalize');
});
