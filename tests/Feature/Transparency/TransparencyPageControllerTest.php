<?php

use App\Models\Application;
use App\Models\TransparencyPage;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access transparency pages', function () {
    $this->get('/applications/1/transparency')->assertRedirect('/login');
});

test('show creates transparency page if not exists', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/applications/{$application->id}/transparency")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('transparency/show')
                ->has('page')
                ->has('application')
        );

    expect($application->transparencyPage)->not->toBeNull();
});

test('show loads existing transparency page', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);
    $page = TransparencyPage::factory()->create([
        'user_id' => $this->user->id,
        'application_id' => $application->id,
    ]);

    $this->actingAs($this->user)
        ->get("/applications/{$application->id}/transparency")
        ->assertSuccessful()
        ->assertInertia(
            fn ($inertia) => $inertia
                ->where('page.id', $page->id)
        );
});

test('show returns 403 for other users application', function () {
    $other = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/applications/{$application->id}/transparency")
        ->assertForbidden();
});

test('update modifies transparency page', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);
    TransparencyPage::factory()->create([
        'user_id' => $this->user->id,
        'application_id' => $application->id,
    ]);

    $this->actingAs($this->user)
        ->put("/applications/{$application->id}/transparency", [
            'authorship_statement' => 'Updated statement',
            'research_summary' => 'Updated research',
        ])
        ->assertRedirect();

    expect($application->transparencyPage->fresh())
        ->authorship_statement->toBe('Updated statement')
        ->research_summary->toBe('Updated research');
});

test('publish marks page as published', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);
    TransparencyPage::factory()->create([
        'user_id' => $this->user->id,
        'application_id' => $application->id,
        'authorship_statement' => 'Test statement',
    ]);

    $this->actingAs($this->user)
        ->post("/applications/{$application->id}/transparency/publish")
        ->assertRedirect();

    expect($application->transparencyPage->fresh())
        ->is_published->toBeTrue()
        ->content_html->not->toBeNull();
});

test('public page shows published page', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);
    $page = TransparencyPage::factory()->published()->create([
        'user_id' => $this->user->id,
        'application_id' => $application->id,
    ]);

    $this->get("/t/{$page->slug}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($inertia) => $inertia
                ->component('transparency/public')
                ->has('page')
        );
});

test('public page returns 404 for unpublished page', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);
    $page = TransparencyPage::factory()->create([
        'user_id' => $this->user->id,
        'application_id' => $application->id,
        'is_published' => false,
    ]);

    $this->get("/t/{$page->slug}")
        ->assertNotFound();
});

test('public page returns 404 for non-existent slug', function () {
    $this->get('/t/non-existent-slug-12345')
        ->assertNotFound();
});
