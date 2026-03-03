<?php

use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->experience = Experience::factory()->create(['user_id' => $this->user->id]);
});

test('store creates project linked to experience', function () {
    $this->actingAs($this->user)->post('/projects', [
        'experience_id' => $this->experience->id,
        'name' => 'Customer Portal',
        'description' => 'Redesigned the portal',
        'role' => 'Tech Lead',
        'sort_order' => 0,
    ])->assertRedirect("/experiences/{$this->experience->id}");

    expect(Project::first())
        ->name->toBe('Customer Portal')
        ->experience_id->toBe($this->experience->id)
        ->user_id->toBe($this->user->id);
});

test('store creates standalone project', function () {
    $this->actingAs($this->user)->post('/projects', [
        'name' => 'Side project',
        'description' => 'Built something cool',
        'sort_order' => 0,
    ])->assertRedirect('/experience-library');

    expect(Project::first()->experience_id)->toBeNull();
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/projects', [])
        ->assertSessionHasErrors(['name', 'description']);
});

test('store validates url format', function () {
    $this->actingAs($this->user)
        ->post('/projects', [
            'name' => 'Test',
            'description' => 'Test',
            'url' => 'not-a-url',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('url');
});

test('store syncs skills', function () {
    $skills = Skill::factory()->count(2)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)->post('/projects', [
        'experience_id' => $this->experience->id,
        'name' => 'Test',
        'description' => 'Test description',
        'sort_order' => 0,
        'skill_ids' => $skills->pluck('id')->toArray(),
    ]);

    expect(Project::first()->skills)->toHaveCount(2);
});

test('update modifies project', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $this->experience->id,
    ]);

    $this->actingAs($this->user)
        ->put("/projects/{$project->id}", [
            'name' => 'Updated name',
            'description' => 'Updated description',
            'sort_order' => 0,
        ])
        ->assertRedirect("/experiences/{$this->experience->id}");

    expect($project->fresh()->name)->toBe('Updated name');
});

test('update returns 403 for other users project', function () {
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/projects/{$project->id}", [
            'name' => 'Hacked',
            'description' => 'Hacked',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('destroy deletes project', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $this->experience->id,
    ]);

    $this->actingAs($this->user)
        ->delete("/projects/{$project->id}")
        ->assertRedirect("/experiences/{$this->experience->id}");

    expect(Project::find($project->id))->toBeNull();
});

test('destroy returns 403 for other users project', function () {
    $other = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/projects/{$project->id}")
        ->assertForbidden();
});
