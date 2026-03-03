<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatusChange;
use App\Models\TransparencyPage;
use App\Models\User;

// Application
test('application factory creates valid model', function () {
    $application = Application::factory()->create();

    expect($application)->toBeInstanceOf(Application::class)
        ->and($application->status)->toBeInstanceOf(ApplicationStatus::class)
        ->and($application->company)->toBeString();
});

test('application belongs to user', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    expect($application->user->id)->toBe($user->id);
});

test('application draft state', function () {
    $application = Application::factory()->draft()->create();

    expect($application->status)->toBe(ApplicationStatus::Draft)
        ->and($application->applied_at)->toBeNull();
});

test('application applied state', function () {
    $application = Application::factory()->applied()->create();

    expect($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->applied_at)->not->toBeNull();
});

test('application has many notes', function () {
    $application = Application::factory()->create();
    ApplicationNote::factory()->count(3)->create(['application_id' => $application->id]);

    expect($application->applicationNotes)->toHaveCount(3);
});

test('application has many status changes', function () {
    $application = Application::factory()->create();
    ApplicationStatusChange::factory()->count(3)->create(['application_id' => $application->id]);

    expect($application->statusChanges)->toHaveCount(3);
});

test('application has one transparency page', function () {
    $application = Application::factory()->create();
    TransparencyPage::factory()->create([
        'user_id' => $application->user_id,
        'application_id' => $application->id,
    ]);

    expect($application->transparencyPage)->toBeInstanceOf(TransparencyPage::class);
});

test('user has many applications', function () {
    $user = User::factory()->create();
    Application::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->applications)->toHaveCount(3);
});

test('deleting application cascades to notes and status changes', function () {
    $application = Application::factory()->create();
    $note = ApplicationNote::factory()->create(['application_id' => $application->id]);
    $statusChange = ApplicationStatusChange::factory()->create(['application_id' => $application->id]);

    $application->delete();

    expect(ApplicationNote::find($note->id))->toBeNull()
        ->and(ApplicationStatusChange::find($statusChange->id))->toBeNull();
});

// Application Note
test('application note factory creates valid model', function () {
    $note = ApplicationNote::factory()->create();

    expect($note)->toBeInstanceOf(ApplicationNote::class)
        ->and($note->content)->toBeString();
});

// Application Status Change
test('application status change factory creates valid model', function () {
    $change = ApplicationStatusChange::factory()->create();

    expect($change)->toBeInstanceOf(ApplicationStatusChange::class)
        ->and($change->to_status)->toBeInstanceOf(ApplicationStatus::class);
});

test('application status change casts enums', function () {
    $change = ApplicationStatusChange::factory()->create([
        'from_status' => ApplicationStatus::Draft,
        'to_status' => ApplicationStatus::Applied,
    ]);

    expect($change->from_status)->toBe(ApplicationStatus::Draft)
        ->and($change->to_status)->toBe(ApplicationStatus::Applied);
});

// Transparency Page
test('transparency page factory creates valid model', function () {
    $page = TransparencyPage::factory()->create();

    expect($page)->toBeInstanceOf(TransparencyPage::class)
        ->and($page->slug)->toBeString()
        ->and($page->section_decisions)->toBeArray();
});

test('transparency page published state', function () {
    $page = TransparencyPage::factory()->published()->create();

    expect($page->is_published)->toBeTrue()
        ->and($page->content_html)->not->toBeNull();
});

test('transparency page slug is unique', function () {
    TransparencyPage::factory()->create(['slug' => 'test-slug']);

    expect(fn () => TransparencyPage::factory()->create(['slug' => 'test-slug']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
