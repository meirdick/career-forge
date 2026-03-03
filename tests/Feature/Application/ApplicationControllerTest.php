<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access application pages', function () {
    $this->get('/applications')->assertRedirect('/login');
    $this->post('/applications')->assertRedirect('/login');
});

test('index displays applications', function () {
    Application::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/applications')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('applications/index')
                ->has('applications', 3)
        );
});

test('create page renders', function () {
    $this->actingAs($this->user)
        ->get('/applications/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('applications/create'));
});

test('store creates application with draft status', function () {
    $this->actingAs($this->user)
        ->post('/applications', [
            'company' => 'Acme Corp',
            'role' => 'Senior Developer',
        ])
        ->assertRedirect();

    $application = Application::first();
    expect($application)
        ->user_id->toBe($this->user->id)
        ->company->toBe('Acme Corp')
        ->role->toBe('Senior Developer')
        ->status->toBe(ApplicationStatus::Draft);

    expect($application->statusChanges)->toHaveCount(1);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/applications', [])
        ->assertSessionHasErrors(['company', 'role']);
});

test('show displays application with details', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/applications/{$application->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('applications/show')
                ->has('application')
        );
});

test('show returns 403 for other users application', function () {
    $other = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/applications/{$application->id}")
        ->assertForbidden();
});

test('update modifies application', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/applications/{$application->id}", [
            'company' => 'Updated Corp',
            'role' => 'Updated Role',
        ])
        ->assertRedirect();

    expect($application->fresh())
        ->company->toBe('Updated Corp')
        ->role->toBe('Updated Role');
});

test('update status changes status and records history', function () {
    $application = Application::factory()->draft()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->patch("/applications/{$application->id}/status", [
            'status' => 'applied',
            'notes' => 'Submitted via email',
        ])
        ->assertRedirect();

    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::Applied);
    expect($application->applied_at)->not->toBeNull();
    expect($application->statusChanges)->toHaveCount(1);
    expect($application->statusChanges->first())
        ->from_status->toBe(ApplicationStatus::Draft)
        ->to_status->toBe(ApplicationStatus::Applied)
        ->notes->toBe('Submitted via email');
});

test('update status validates status enum', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->patch("/applications/{$application->id}/status", [
            'status' => 'invalid_status',
        ])
        ->assertSessionHasErrors('status');
});

test('destroy deletes application', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/applications/{$application->id}")
        ->assertRedirect('/applications');

    expect(Application::find($application->id))->toBeNull();
});

test('destroy returns 403 for other users application', function () {
    $other = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/applications/{$application->id}")
        ->assertForbidden();
});
