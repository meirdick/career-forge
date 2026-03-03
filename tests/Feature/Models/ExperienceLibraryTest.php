<?php

use App\Enums\EducationType;
use App\Enums\SkillCategory;
use App\Enums\SkillProficiency;
use App\Models\Accomplishment;
use App\Models\EducationEntry;
use App\Models\EvidenceEntry;
use App\Models\Experience;
use App\Models\ProfessionalIdentity;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;

// Experience
test('experience factory creates valid model', function () {
    $experience = Experience::factory()->create();

    expect($experience)->toBeInstanceOf(Experience::class)
        ->and($experience->user)->toBeInstanceOf(User::class)
        ->and($experience->company)->toBeString()
        ->and($experience->started_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});

test('experience belongs to user', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $user->id]);

    expect($experience->user->id)->toBe($user->id);
});

test('experience has many accomplishments', function () {
    $experience = Experience::factory()->create();
    Accomplishment::factory()->count(3)->create([
        'user_id' => $experience->user_id,
        'experience_id' => $experience->id,
    ]);

    expect($experience->accomplishments)->toHaveCount(3);
});

test('experience has many projects', function () {
    $experience = Experience::factory()->create();
    Project::factory()->count(2)->create([
        'user_id' => $experience->user_id,
        'experience_id' => $experience->id,
    ]);

    expect($experience->projects)->toHaveCount(2);
});

test('experience belongs to many skills', function () {
    $experience = Experience::factory()->create();
    $skills = Skill::factory()->count(3)->create(['user_id' => $experience->user_id]);
    $experience->skills()->attach($skills->pluck('id'));

    expect($experience->skills)->toHaveCount(3);
});

test('user has many experiences', function () {
    $user = User::factory()->create();
    Experience::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->experiences)->toHaveCount(3);
});

test('experience casts dates correctly', function () {
    $experience = Experience::factory()->create([
        'started_at' => '2020-01-15',
        'ended_at' => '2023-06-30',
        'is_current' => false,
    ]);

    expect($experience->started_at->format('Y-m-d'))->toBe('2020-01-15')
        ->and($experience->ended_at->format('Y-m-d'))->toBe('2023-06-30')
        ->and($experience->is_current)->toBeFalse();
});

// Accomplishment
test('accomplishment factory creates valid model', function () {
    $accomplishment = Accomplishment::factory()->create();

    expect($accomplishment)->toBeInstanceOf(Accomplishment::class)
        ->and($accomplishment->title)->toBeString();
});

test('accomplishment belongs to experience', function () {
    $experience = Experience::factory()->create();
    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $experience->user_id,
        'experience_id' => $experience->id,
    ]);

    expect($accomplishment->experience->id)->toBe($experience->id);
});

test('accomplishment can be standalone', function () {
    $accomplishment = Accomplishment::factory()->standalone()->create();

    expect($accomplishment->experience_id)->toBeNull()
        ->and($accomplishment->experience)->toBeNull();
});

test('accomplishment belongs to many skills', function () {
    $accomplishment = Accomplishment::factory()->create();
    $skills = Skill::factory()->count(2)->create(['user_id' => $accomplishment->user_id]);
    $accomplishment->skills()->attach($skills->pluck('id'));

    expect($accomplishment->skills)->toHaveCount(2);
});

// Project
test('project factory creates valid model', function () {
    $project = Project::factory()->create();

    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->name)->toBeString();
});

test('project belongs to experience', function () {
    $experience = Experience::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $experience->user_id,
        'experience_id' => $experience->id,
    ]);

    expect($project->experience->id)->toBe($experience->id);
});

test('project can be standalone', function () {
    $project = Project::factory()->standalone()->create();

    expect($project->experience_id)->toBeNull();
});

test('project belongs to many skills', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(2)->create(['user_id' => $project->user_id]);
    $project->skills()->attach($skills->pluck('id'));

    expect($project->skills)->toHaveCount(2);
});

// Skill
test('skill factory creates valid model', function () {
    $skill = Skill::factory()->create();

    expect($skill)->toBeInstanceOf(Skill::class)
        ->and($skill->category)->toBeInstanceOf(SkillCategory::class);
});

test('skill casts enums correctly', function () {
    $skill = Skill::factory()->create([
        'category' => SkillCategory::Technical,
        'proficiency' => SkillProficiency::Expert,
    ]);

    expect($skill->category)->toBe(SkillCategory::Technical)
        ->and($skill->proficiency)->toBe(SkillProficiency::Expert);
});

test('skill has unique constraint per user and name', function () {
    $user = User::factory()->create();
    Skill::factory()->create(['user_id' => $user->id, 'name' => 'PHP']);

    expect(fn () => Skill::factory()->create(['user_id' => $user->id, 'name' => 'PHP']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('skill belongs to many experiences', function () {
    $skill = Skill::factory()->create();
    $experiences = Experience::factory()->count(2)->create(['user_id' => $skill->user_id]);
    $skill->experiences()->attach($experiences->pluck('id'));

    expect($skill->experiences)->toHaveCount(2);
});

// Professional Identity
test('professional identity factory creates valid model', function () {
    $identity = ProfessionalIdentity::factory()->create();

    expect($identity)->toBeInstanceOf(ProfessionalIdentity::class)
        ->and($identity->user)->toBeInstanceOf(User::class);
});

test('professional identity is one-to-one with user', function () {
    $user = User::factory()->create();
    ProfessionalIdentity::factory()->create(['user_id' => $user->id]);

    expect($user->professionalIdentity)->toBeInstanceOf(ProfessionalIdentity::class);
});

test('professional identity enforces unique user', function () {
    $user = User::factory()->create();
    ProfessionalIdentity::factory()->create(['user_id' => $user->id]);

    expect(fn () => ProfessionalIdentity::factory()->create(['user_id' => $user->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// Education Entry
test('education entry factory creates valid model', function () {
    $entry = EducationEntry::factory()->create();

    expect($entry)->toBeInstanceOf(EducationEntry::class)
        ->and($entry->type)->toBeInstanceOf(EducationType::class);
});

test('education entry belongs to user', function () {
    $user = User::factory()->create();
    EducationEntry::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->educationEntries)->toHaveCount(2);
});

// Evidence Entry
test('evidence entry factory creates valid model', function () {
    $entry = EvidenceEntry::factory()->create();

    expect($entry)->toBeInstanceOf(EvidenceEntry::class)
        ->and($entry->type)->toBeString();
});

test('evidence entry belongs to user', function () {
    $user = User::factory()->create();
    EvidenceEntry::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->evidenceEntries)->toHaveCount(2);
});
