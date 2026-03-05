<?php

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
use App\Services\ExperienceLibraryContextService;

test('returns empty string for user with no experience data', function () {
    $user = User::factory()->create();

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)->toBe('');
});

test('includes work experience with nested accomplishments and projects', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create([
        'user_id' => $user->id,
        'company' => 'Acme Corp',
        'title' => 'Senior Developer',
        'location' => 'San Francisco, CA',
        'team_size' => 8,
        'is_current' => true,
        'ended_at' => null,
    ]);

    Accomplishment::factory()->create([
        'user_id' => $user->id,
        'experience_id' => $experience->id,
        'title' => 'Reduced deploy time by 50%',
        'impact' => 'Saved 20 hours per week',
    ]);

    Project::factory()->create([
        'user_id' => $user->id,
        'experience_id' => $experience->id,
        'name' => 'Platform Migration',
        'role' => 'Tech Lead',
        'outcome' => 'Successfully migrated 10M users',
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain("USER'S EXPERIENCE LIBRARY")
        ->toContain('Senior Developer at Acme Corp')
        ->toContain('San Francisco, CA')
        ->toContain('Team size: 8')
        ->toContain('Present')
        ->toContain('Reduced deploy time by 50%')
        ->toContain('Saved 20 hours per week')
        ->toContain('Platform Migration')
        ->toContain('Tech Lead')
        ->toContain('Successfully migrated 10M users');
});

test('includes skills grouped by category', function () {
    $user = User::factory()->create();

    Skill::factory()->create([
        'user_id' => $user->id,
        'name' => 'PHP',
        'category' => SkillCategory::Technical,
        'proficiency' => SkillProficiency::Expert,
    ]);

    Skill::factory()->create([
        'user_id' => $user->id,
        'name' => 'Leadership',
        'category' => SkillCategory::Soft,
        'proficiency' => SkillProficiency::Advanced,
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain('## Skills')
        ->toContain('PHP (expert)')
        ->toContain('Leadership (advanced)')
        ->toContain('Technical:')
        ->toContain('Soft:');
});

test('includes education entries', function () {
    $user = User::factory()->create();

    EducationEntry::factory()->create([
        'user_id' => $user->id,
        'title' => 'Bachelor of Science',
        'field' => 'Computer Science',
        'institution' => 'MIT',
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain('## Education & Credentials')
        ->toContain('Bachelor of Science')
        ->toContain('in Computer Science')
        ->toContain('MIT');
});

test('includes professional identity', function () {
    $user = User::factory()->create();

    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'values' => 'Integrity and excellence',
        'leadership_style' => 'Servant leadership',
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain('## Professional Identity')
        ->toContain('Integrity and excellence')
        ->toContain('Servant leadership');
});

test('includes evidence entries', function () {
    $user = User::factory()->create();

    EvidenceEntry::factory()->create([
        'user_id' => $user->id,
        'type' => 'portfolio',
        'title' => 'My GitHub Profile',
        'url' => 'https://github.com/example',
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain('## Evidence & Portfolio')
        ->toContain('[portfolio] My GitHub Profile')
        ->toContain('https://github.com/example');
});

test('omits empty sections', function () {
    $user = User::factory()->create();

    // Only create skills, no other data
    Skill::factory()->create([
        'user_id' => $user->id,
        'name' => 'PHP',
        'category' => SkillCategory::Technical,
    ]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->toContain('## Skills')
        ->not->toContain('## Work Experience')
        ->not->toContain('## Education')
        ->not->toContain('## Professional Identity')
        ->not->toContain('## Evidence');
});

test('omits professional identity section when all fields are null', function () {
    $user = User::factory()->create();

    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'values' => null,
        'philosophy' => null,
        'passions' => null,
        'leadership_style' => null,
        'collaboration_approach' => null,
        'communication_style' => null,
        'cultural_preferences' => null,
    ]);

    // Add a skill so we get some context output
    Skill::factory()->create(['user_id' => $user->id, 'name' => 'PHP', 'category' => SkillCategory::Technical]);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)
        ->not->toContain('## Professional Identity');
});

test('includes skills used on experience via pivot', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $user->id, 'company' => 'TestCo', 'title' => 'Dev']);
    $skill = Skill::factory()->create(['user_id' => $user->id, 'name' => 'Laravel', 'category' => SkillCategory::Technical]);

    $experience->skills()->attach($skill);

    $context = ExperienceLibraryContextService::buildContext($user);

    expect($context)->toContain('Skills used: Laravel');
});
