<?php

use App\Models\Accomplishment;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->experience = Experience::factory()->create(['user_id' => $this->user->id]);
});

test('store creates accomplishment linked to experience', function () {
    $this->actingAs($this->user)->post('/accomplishments', [
        'experience_id' => $this->experience->id,
        'title' => 'Led migration',
        'description' => 'Migrated monolith to microservices',
        'impact' => 'Reduced deploy time by 70%',
        'sort_order' => 0,
    ])->assertRedirect("/experiences/{$this->experience->id}");

    expect(Accomplishment::first())
        ->title->toBe('Led migration')
        ->experience_id->toBe($this->experience->id)
        ->user_id->toBe($this->user->id);
});

test('store creates standalone accomplishment', function () {
    $this->actingAs($this->user)->post('/accomplishments', [
        'title' => 'Standalone achievement',
        'description' => 'Did something great',
        'sort_order' => 0,
    ])->assertRedirect('/experience-library');

    expect(Accomplishment::first())
        ->experience_id->toBeNull();
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/accomplishments', [])
        ->assertSessionHasErrors(['title', 'description']);
});

test('store syncs skills', function () {
    $skills = Skill::factory()->count(2)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)->post('/accomplishments', [
        'experience_id' => $this->experience->id,
        'title' => 'Test',
        'description' => 'Test description',
        'sort_order' => 0,
        'skill_ids' => $skills->pluck('id')->toArray(),
    ]);

    expect(Accomplishment::first()->skills)->toHaveCount(2);
});

test('update modifies accomplishment', function () {
    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $this->experience->id,
    ]);

    $this->actingAs($this->user)
        ->put("/accomplishments/{$accomplishment->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'sort_order' => 0,
        ])
        ->assertRedirect("/experiences/{$this->experience->id}");

    expect($accomplishment->fresh()->title)->toBe('Updated title');
});

test('store rejects experience_id belonging to another user', function () {
    $other = User::factory()->create();
    $otherExperience = Experience::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)->post('/accomplishments', [
        'experience_id' => $otherExperience->id,
        'title' => 'Test',
        'description' => 'Test description',
        'sort_order' => 0,
    ])->assertSessionHasErrors('experience_id');

    expect(Accomplishment::count())->toBe(0);
});

test('update rejects experience_id belonging to another user', function () {
    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $this->experience->id,
    ]);
    $other = User::factory()->create();
    $otherExperience = Experience::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/accomplishments/{$accomplishment->id}", [
            'experience_id' => $otherExperience->id,
            'title' => 'Test',
            'description' => 'Test description',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('experience_id');

    expect($accomplishment->fresh()->experience_id)->toBe($this->experience->id);
});

test('store does not attach skills belonging to another user', function () {
    $other = User::factory()->create();
    $otherSkill = Skill::factory()->create(['user_id' => $other->id]);
    $ownSkill = Skill::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)->post('/accomplishments', [
        'title' => 'Test',
        'description' => 'Test description',
        'sort_order' => 0,
        'skill_ids' => [$otherSkill->id, $ownSkill->id],
    ]);

    $skillIds = Accomplishment::first()->skills->pluck('id');
    expect($skillIds)->toHaveCount(1)
        ->and($skillIds)->toContain($ownSkill->id)
        ->and($skillIds)->not->toContain($otherSkill->id);
});

test('update returns 403 for other users accomplishment', function () {
    $other = User::factory()->create();
    $accomplishment = Accomplishment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/accomplishments/{$accomplishment->id}", [
            'title' => 'Hacked',
            'description' => 'Hacked',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('destroy deletes accomplishment', function () {
    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'experience_id' => $this->experience->id,
    ]);

    $this->actingAs($this->user)
        ->delete("/accomplishments/{$accomplishment->id}")
        ->assertRedirect("/experiences/{$this->experience->id}");

    expect(Accomplishment::find($accomplishment->id))->toBeNull();
});

test('destroy returns 403 for other users accomplishment', function () {
    $other = User::factory()->create();
    $accomplishment = Accomplishment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/accomplishments/{$accomplishment->id}")
        ->assertForbidden();
});
