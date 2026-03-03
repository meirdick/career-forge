<?php

use App\Enums\SkillCategory;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index displays skills grouped by category', function () {
    Skill::factory()->create(['user_id' => $this->user->id, 'category' => SkillCategory::Technical]);
    Skill::factory()->create(['user_id' => $this->user->id, 'category' => SkillCategory::Soft]);

    $this->actingAs($this->user)
        ->get('/skills')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/skills')
                ->has('skillsByCategory')
        );
});

test('store creates skill', function () {
    $this->actingAs($this->user)->post('/skills', [
        'name' => 'Laravel',
        'category' => 'technical',
    ])->assertRedirect('/skills');

    expect(Skill::first())
        ->name->toBe('Laravel')
        ->category->toBe(SkillCategory::Technical)
        ->user_id->toBe($this->user->id);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/skills', [])
        ->assertSessionHasErrors(['name', 'category']);
});

test('store validates category must be valid enum', function () {
    $this->actingAs($this->user)
        ->post('/skills', [
            'name' => 'Test',
            'category' => 'invalid',
        ])
        ->assertSessionHasErrors('category');
});

test('update modifies skill', function () {
    $skill = Skill::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/skills/{$skill->id}", [
            'name' => 'Updated Skill',
            'category' => 'soft',
        ])
        ->assertRedirect('/skills');

    expect($skill->fresh())
        ->name->toBe('Updated Skill')
        ->category->toBe(SkillCategory::Soft);
});

test('update returns 403 for other users skill', function () {
    $other = User::factory()->create();
    $skill = Skill::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/skills/{$skill->id}", [
            'name' => 'Hacked',
            'category' => 'technical',
        ])
        ->assertForbidden();
});

test('destroy deletes skill', function () {
    $skill = Skill::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/skills/{$skill->id}")
        ->assertRedirect('/skills');

    expect(Skill::find($skill->id))->toBeNull();
});

test('destroy returns 403 for other users skill', function () {
    $other = User::factory()->create();
    $skill = Skill::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/skills/{$skill->id}")
        ->assertForbidden();
});
