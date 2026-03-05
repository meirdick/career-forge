<?php

use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;
use App\Services\ExperienceImportService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = new ExperienceImportService;
});

test('import creates experiences', function () {
    $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => true],
        ],
    ]);

    expect($this->user->experiences()->count())->toBe(1);
    expect($this->user->experiences()->first())
        ->company->toBe('Acme')
        ->title->toBe('Dev');
});

test('import creates skills with firstOrCreate', function () {
    Skill::factory()->create(['user_id' => $this->user->id, 'name' => 'PHP', 'category' => 'technical']);

    $this->service->import($this->user, [
        'skills' => [
            ['name' => 'PHP', 'category' => 'technical'],
            ['name' => 'Laravel', 'category' => 'technical'],
        ],
    ]);

    expect($this->user->skills()->count())->toBe(2);
});

test('import links accomplishments to experiences', function () {
    $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => true],
        ],
        'accomplishments' => [
            ['title' => 'Built API', 'description' => 'REST API', 'experience_index' => 0],
            ['title' => 'Solo project', 'description' => 'No link'],
        ],
    ]);

    $experience = Experience::first();
    $linked = $this->user->accomplishments()->where('experience_id', $experience->id)->first();
    $unlinked = $this->user->accomplishments()->whereNull('experience_id')->first();

    expect($linked)->not->toBeNull();
    expect($linked->title)->toBe('Built API');
    expect($unlinked)->not->toBeNull();
    expect($unlinked->title)->toBe('Solo project');
});

test('import creates education entries', function () {
    $this->service->import($this->user, [
        'education' => [
            ['type' => 'degree', 'institution' => 'MIT', 'title' => 'CS'],
        ],
    ]);

    expect($this->user->educationEntries()->count())->toBe(1);
});

test('import links projects to experiences', function () {
    $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => true],
        ],
        'projects' => [
            ['name' => 'Portal', 'description' => 'Customer portal', 'experience_index' => 0],
        ],
    ]);

    $experience = Experience::first();
    $project = $this->user->projects()->first();

    expect($project->experience_id)->toBe($experience->id);
});

test('import handles nullish values', function () {
    $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => false, 'ended_at' => 'null', 'location' => ''],
        ],
    ]);

    $exp = $this->user->experiences()->first();
    expect($exp->ended_at)->toBeNull();
    expect($exp->location)->toBeNull();
});

test('import handles empty data gracefully', function () {
    $this->service->import($this->user, []);

    expect($this->user->experiences()->count())->toBe(0);
    expect($this->user->skills()->count())->toBe(0);
});
