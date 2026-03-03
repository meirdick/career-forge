<?php

use App\Models\Accomplishment;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access experience pages', function () {
    $this->get('/experience-library')->assertRedirect('/login');
    $this->get('/experiences/create')->assertRedirect('/login');
    $this->post('/experiences')->assertRedirect('/login');
});

test('index displays timeline ordered by current then start date', function () {
    $old = Experience::factory()->create([
        'user_id' => $this->user->id,
        'started_at' => '2020-01-01',
        'is_current' => false,
    ]);
    $current = Experience::factory()->current()->create([
        'user_id' => $this->user->id,
        'started_at' => '2023-01-01',
    ]);

    $response = $this->actingAs($this->user)->get('/experience-library');

    $response->assertSuccessful();
    $response->assertInertia(
        fn ($page) => $page
            ->component('experience-library/index')
            ->has('experiences', 2)
            ->where('experiences.0.id', $current->id)
            ->where('experiences.1.id', $old->id)
    );
});

test('create page renders', function () {
    $this->actingAs($this->user)
        ->get('/experiences/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('experience-library/experiences/create'));
});

test('store creates experience and redirects to show', function () {
    $response = $this->actingAs($this->user)->post('/experiences', [
        'company' => 'Acme Corp',
        'title' => 'Senior Engineer',
        'started_at' => '2023-01-15',
        'is_current' => true,
        'sort_order' => 0,
    ]);

    $experience = Experience::first();
    $response->assertRedirect("/experiences/{$experience->id}");
    expect($experience)
        ->company->toBe('Acme Corp')
        ->title->toBe('Senior Engineer')
        ->user_id->toBe($this->user->id);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/experiences', [])
        ->assertSessionHasErrors(['company', 'title', 'started_at']);
});

test('store validates ended_at must be after started_at', function () {
    $this->actingAs($this->user)
        ->post('/experiences', [
            'company' => 'Test',
            'title' => 'Test',
            'started_at' => '2024-06-01',
            'ended_at' => '2024-01-01',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('ended_at');
});

test('store syncs skills when provided', function () {
    $skills = Skill::factory()->count(2)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)->post('/experiences', [
        'company' => 'Acme',
        'title' => 'Dev',
        'started_at' => '2023-01-01',
        'sort_order' => 0,
        'skill_ids' => $skills->pluck('id')->toArray(),
    ]);

    expect(Experience::first()->skills)->toHaveCount(2);
});

test('show displays experience with relations', function () {
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);
    Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $experience->id,
    ]);

    $this->actingAs($this->user)
        ->get("/experiences/{$experience->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/experiences/show')
                ->has('experience.accomplishments', 1)
        );
});

test('show returns 403 for other users experience', function () {
    $other = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/experiences/{$experience->id}")
        ->assertForbidden();
});

test('edit page renders with experience data', function () {
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/experiences/{$experience->id}/edit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/experiences/edit')
                ->where('experience.id', $experience->id)
        );
});

test('update modifies experience', function () {
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/experiences/{$experience->id}", [
            'company' => 'Updated Corp',
            'title' => 'Lead Engineer',
            'started_at' => '2023-01-01',
            'sort_order' => 0,
        ])
        ->assertRedirect("/experiences/{$experience->id}");

    expect($experience->fresh())
        ->company->toBe('Updated Corp')
        ->title->toBe('Lead Engineer');
});

test('update returns 403 for other users experience', function () {
    $other = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/experiences/{$experience->id}", [
            'company' => 'Hacked',
            'title' => 'Hacked',
            'started_at' => '2023-01-01',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('destroy deletes experience and redirects', function () {
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/experiences/{$experience->id}")
        ->assertRedirect('/experience-library');

    expect(Experience::find($experience->id))->toBeNull();
});

test('destroy returns 403 for other users experience', function () {
    $other = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/experiences/{$experience->id}")
        ->assertForbidden();
});

test('index filters by skill', function () {
    $skill = Skill::factory()->create(['user_id' => $this->user->id, 'name' => 'PHP']);
    $matching = Experience::factory()->create(['user_id' => $this->user->id, 'company' => 'PHP Co']);
    $matching->skills()->attach($skill);
    Experience::factory()->create(['user_id' => $this->user->id, 'company' => 'Other Co']);

    $this->actingAs($this->user)
        ->get("/experience-library?skill_id={$skill->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->has('experiences', 1)
                ->where('experiences.0.company', 'PHP Co')
        );
});

test('index filters by date range', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'started_at' => '2020-01-01',
        'ended_at' => '2021-01-01',
        'is_current' => false,
    ]);
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'started_at' => '2023-01-01',
        'is_current' => true,
    ]);

    $this->actingAs($this->user)
        ->get('/experience-library?from=2022-01-01')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('experiences', 1));
});

test('index returns skills for filter dropdown', function () {
    Skill::factory()->create(['user_id' => $this->user->id, 'name' => 'Laravel']);

    $this->actingAs($this->user)
        ->get('/experience-library')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->has('skills', 1)
                ->has('filters')
        );
});
