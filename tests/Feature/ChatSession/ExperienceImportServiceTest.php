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
    $stats = $this->service->import($this->user, []);

    expect($this->user->experiences()->count())->toBe(0);
    expect($this->user->skills()->count())->toBe(0);
    expect($stats)->toBe(['created' => 0, 'merged' => 0, 'skipped' => 0]);
});

test('import returns stats with created count', function () {
    $stats = $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => true],
        ],
        'skills' => [
            ['name' => 'PHP', 'category' => 'technical'],
        ],
    ]);

    expect($stats['created'])->toBe(2);
    expect($stats['merged'])->toBe(0);
    expect($stats['skipped'])->toBe(0);
});

test('import merges experience by company and title and fills blank fields', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'Acme',
        'title' => 'Dev',
        'started_at' => '2023-01-01',
        'location' => null,
        'description' => null,
    ]);

    $stats = $this->service->import($this->user, [
        'experiences' => [
            [
                'company' => 'acme', // case-insensitive match
                'title' => 'dev',
                'started_at' => '2023-02-01', // within 90-day overlap
                'is_current' => true,
                'location' => 'San Francisco',
                'description' => 'Built APIs',
            ],
        ],
    ]);

    expect($this->user->experiences()->count())->toBe(1);
    expect($this->user->experiences()->first())
        ->location->toBe('San Francisco')
        ->description->toBe('Built APIs');
    expect($stats['merged'])->toBe(1);
    expect($stats['created'])->toBe(0);
});

test('import does not overwrite existing experience fields', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'Acme',
        'title' => 'Dev',
        'started_at' => '2023-01-01',
        'location' => 'New York',
        'description' => 'Existing description',
    ]);

    $stats = $this->service->import($this->user, [
        'experiences' => [
            [
                'company' => 'Acme',
                'title' => 'Dev',
                'started_at' => '2023-01-01',
                'is_current' => true,
                'location' => 'San Francisco',
                'description' => 'New description',
            ],
        ],
    ]);

    expect($this->user->experiences()->first())
        ->location->toBe('New York')
        ->description->toBe('Existing description');
    expect($stats['skipped'])->toBe(1);
});

test('import creates new experience when company or title differs', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'Acme',
        'title' => 'Dev',
        'started_at' => '2023-01-01',
    ]);

    $stats = $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Lead Dev', 'started_at' => '2023-01-01', 'is_current' => true],
        ],
    ]);

    expect($this->user->experiences()->count())->toBe(2);
    expect($stats['created'])->toBe(1);
});

test('import skips duplicate skills', function () {
    Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'PHP',
        'category' => 'technical',
    ]);

    $stats = $this->service->import($this->user, [
        'skills' => [
            ['name' => 'php', 'category' => 'technical'], // case-insensitive
        ],
    ]);

    expect($this->user->skills()->count())->toBe(1);
    expect($stats['skipped'])->toBe(1);
});

test('import skips duplicate skills case-insensitively', function () {
    Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Docker',
        'category' => 'tool',
    ]);

    $stats = $this->service->import($this->user, [
        'skills' => [
            ['name' => 'docker', 'category' => 'tool'],
            ['name' => 'DOCKER', 'category' => 'technical'],
        ],
    ]);

    expect($this->user->skills()->count())->toBe(1);
    expect($stats['skipped'])->toBe(2);
});

test('import merges accomplishment by title and experience', function () {
    $experience = Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'Acme',
        'title' => 'Dev',
        'started_at' => '2023-01-01',
    ]);

    $this->user->accomplishments()->create([
        'experience_id' => $experience->id,
        'title' => 'Built API',
        'description' => 'REST API',
        'impact' => null,
        'sort_order' => 0,
    ]);

    $stats = $this->service->import($this->user, [
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => false],
        ],
        'accomplishments' => [
            ['title' => 'built api', 'description' => 'REST API', 'impact' => 'Reduced latency by 40%', 'experience_index' => 0],
        ],
    ]);

    expect($this->user->accomplishments()->count())->toBe(1);
    expect($this->user->accomplishments()->first()->impact)->toBe('Reduced latency by 40%');
    expect($stats['merged'])->toBeGreaterThanOrEqual(1);
});

test('import merges education by institution and title', function () {
    $this->user->educationEntries()->create([
        'type' => 'degree',
        'institution' => 'MIT',
        'title' => 'CS',
        'field' => null,
        'completed_at' => null,
        'sort_order' => 0,
    ]);

    $stats = $this->service->import($this->user, [
        'education' => [
            ['type' => 'degree', 'institution' => 'mit', 'title' => 'cs', 'field' => 'Computer Science', 'completed_at' => '2020-06-01'],
        ],
    ]);

    expect($this->user->educationEntries()->count())->toBe(1);
    expect($this->user->educationEntries()->first())
        ->field->toBe('Computer Science')
        ->completed_at->not->toBeNull();
    expect($stats['merged'])->toBe(1);
});

test('import merges project by name', function () {
    $this->user->projects()->create([
        'name' => 'Portal',
        'description' => 'Customer portal',
        'role' => null,
        'outcome' => null,
        'sort_order' => 0,
    ]);

    $stats = $this->service->import($this->user, [
        'projects' => [
            ['name' => 'portal', 'description' => 'Customer portal', 'role' => 'Lead Developer', 'outcome' => 'Shipped to 10k users'],
        ],
    ]);

    expect($this->user->projects()->count())->toBe(1);
    expect($this->user->projects()->first())
        ->role->toBe('Lead Developer')
        ->outcome->toBe('Shipped to 10k users');
    expect($stats['merged'])->toBe(1);
});

test('buildImportMessage formats stats correctly', function () {
    expect(ExperienceImportService::buildImportMessage(['created' => 3, 'merged' => 1, 'skipped' => 2]))
        ->toBe('Import complete: 3 new items added, 1 item updated, 2 duplicates skipped.');

    expect(ExperienceImportService::buildImportMessage(['created' => 1, 'merged' => 0, 'skipped' => 0]))
        ->toBe('Import complete: 1 new item added.');

    expect(ExperienceImportService::buildImportMessage(['created' => 0, 'merged' => 0, 'skipped' => 0]))
        ->toBe('No items to import.');
});
