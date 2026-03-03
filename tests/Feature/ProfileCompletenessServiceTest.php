<?php

use App\Models\EducationEntry;
use App\Models\EvidenceEntry;
use App\Models\Experience;
use App\Models\ProfessionalIdentity;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProfileCompletenessService;

test('empty profile returns 0 percent', function () {
    $user = User::factory()->create();
    $service = new ProfileCompletenessService;

    $result = $service->calculate($user);

    expect($result['score'])->toBe(0);
    expect(array_filter($result['items']))->toBeEmpty();
});

test('complete profile returns 100 percent', function () {
    $user = User::factory()->create();

    $experience = Experience::factory()->create(['user_id' => $user->id]);
    $experience->accomplishments()->create([
        'user_id' => $user->id,
        'title' => 'Test accomplishment',
        'description' => 'Test description',
        'sort_order' => 0,
    ]);
    Skill::factory()->count(5)->create(['user_id' => $user->id]);
    EducationEntry::factory()->create(['user_id' => $user->id]);
    ProfessionalIdentity::factory()->create(['user_id' => $user->id]);
    EvidenceEntry::factory()->create(['user_id' => $user->id]);
    Project::factory()->create(['user_id' => $user->id]);

    $service = new ProfileCompletenessService;
    $result = $service->calculate($user);

    expect($result['score'])->toBe(100);
});

test('partial profile returns intermediate score', function () {
    $user = User::factory()->create();

    Experience::factory()->create(['user_id' => $user->id]);
    Skill::factory()->count(5)->create(['user_id' => $user->id]);

    $service = new ProfileCompletenessService;
    $result = $service->calculate($user);

    // 2 of 7 items = ~29%
    expect($result['score'])->toBe(29);
    expect($result['items']['Has experiences'])->toBeTrue();
    expect($result['items']['Has skills (5+)'])->toBeTrue();
    expect($result['items']['Has education'])->toBeFalse();
});
