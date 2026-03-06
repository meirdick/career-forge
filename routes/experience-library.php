<?php

use App\Http\Controllers\ExperienceLibrary\AccomplishmentController;
use App\Http\Controllers\ExperienceLibrary\EducationEntryController;
use App\Http\Controllers\ExperienceLibrary\EvidenceEntryController;
use App\Http\Controllers\ExperienceLibrary\ExperienceController;
use App\Http\Controllers\ExperienceLibrary\ExperienceEnhanceController;
use App\Http\Controllers\ExperienceLibrary\ProfessionalIdentityController;
use App\Http\Controllers\ExperienceLibrary\ProjectController;
use App\Http\Controllers\ExperienceLibrary\ResumeUploadController;
use App\Http\Controllers\ExperienceLibrary\SkillController;
use App\Http\Controllers\ExperienceLibrary\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Timeline index
    Route::get('experience-library', [ExperienceController::class, 'index'])->name('experience-library.index');

    // Experiences CRUD
    Route::resource('experiences', ExperienceController::class)->except(['index']);

    // Accomplishments (nested under experiences or standalone)
    Route::post('accomplishments', [AccomplishmentController::class, 'store'])->name('accomplishments.store');
    Route::put('accomplishments/{accomplishment}', [AccomplishmentController::class, 'update'])->name('accomplishments.update');
    Route::delete('accomplishments/{accomplishment}', [AccomplishmentController::class, 'destroy'])->name('accomplishments.destroy');

    // Projects
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Skills
    Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
    Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
    Route::put('skills/{skill}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');

    // Professional Identity
    Route::get('identity', [ProfessionalIdentityController::class, 'edit'])->name('identity.edit');
    Route::put('identity', [ProfessionalIdentityController::class, 'update'])->name('identity.update');

    // Education
    Route::get('education', [EducationEntryController::class, 'index'])->name('education.index');
    Route::post('education', [EducationEntryController::class, 'store'])->name('education.store');
    Route::put('education/{educationEntry}', [EducationEntryController::class, 'update'])->name('education.update');
    Route::delete('education/{educationEntry}', [EducationEntryController::class, 'destroy'])->name('education.destroy');

    // Evidence
    Route::get('evidence', [EvidenceEntryController::class, 'index'])->name('evidence.index');
    Route::post('evidence', [EvidenceEntryController::class, 'store'])->name('evidence.store');
    Route::put('evidence/{evidenceEntry}', [EvidenceEntryController::class, 'update'])->name('evidence.update');
    Route::delete('evidence/{evidenceEntry}', [EvidenceEntryController::class, 'destroy'])->name('evidence.destroy');
    Route::post('evidence/{evidenceEntry}/index-link', [EvidenceEntryController::class, 'indexLink'])->middleware('ai.access:link_indexing')->name('evidence.index-link');
    Route::post('evidence/{evidenceEntry}/import-results', [EvidenceEntryController::class, 'importResults'])->name('evidence.import-results');

    // Tags
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('tags/toggle', [TagController::class, 'toggle'])->name('tags.toggle');

    // Interview (redirects to Career Chat)
    Route::redirect('interview', '/career-chat')->name('interview.index');

    // AI Enhancement
    Route::post('experience-library/enhance', ExperienceEnhanceController::class)->middleware('ai.access:content_enhance')->name('experience-library.enhance');

    // Resume Upload & Import
    Route::get('resume-upload', [ResumeUploadController::class, 'create'])->name('resume-upload.create');
    Route::post('resume-upload', [ResumeUploadController::class, 'store'])->middleware('ai.access:resume_parsing')->name('resume-upload.store');
    Route::get('resume-upload/{document}/review', [ResumeUploadController::class, 'review'])->name('resume-upload.review');
    Route::post('resume-upload/{document}/retry', [ResumeUploadController::class, 'retry'])->middleware('ai.access:resume_parsing')->name('resume-upload.retry');
    Route::post('resume-upload/{document}/commit', [ResumeUploadController::class, 'commit'])->name('resume-upload.commit');
    Route::get('documents/{document}/download', [ResumeUploadController::class, 'download'])->name('documents.download');
});
