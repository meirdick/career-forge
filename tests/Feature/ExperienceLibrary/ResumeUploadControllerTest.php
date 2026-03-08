<?php

use App\Jobs\ParseResumeJob;
use App\Models\Document;
use App\Models\EvidenceEntry;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake();
    Queue::fake();
});

test('upload page renders', function () {
    $this->actingAs($this->user)
        ->get('/resume-upload')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('experience-library/upload'));
});

test('store uploads file and dispatches parse job', function () {
    $file = UploadedFile::fake()->create('resume.pdf', 1024, 'application/pdf');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$file]])
        ->assertRedirect('/resume-upload');

    $document = Document::first();
    expect($document)
        ->filename->toBe('resume.pdf')
        ->mime_type->toBe('application/pdf')
        ->user_id->toBe($this->user->id);

    Queue::assertPushed(ParseResumeJob::class, function ($job) use ($document) {
        return $job->document->id === $document->id
            && $job->user->id === $this->user->id;
    });
});

test('store uploads multiple files and dispatches parse jobs for each', function () {
    $pdf = UploadedFile::fake()->create('resume.pdf', 1024, 'application/pdf');
    $docx = UploadedFile::fake()->create('cv.docx', 512, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$pdf, $docx]])
        ->assertRedirect('/resume-upload');

    expect(Document::count())->toBe(2);
    Queue::assertPushed(ParseResumeJob::class, 2);
});

test('store validates files are required', function () {
    $this->actingAs($this->user)
        ->post('/resume-upload', [])
        ->assertSessionHasErrors('files');
});

test('store validates file type', function () {
    $file = UploadedFile::fake()->create('resume.exe', 1024, 'application/x-executable');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$file]])
        ->assertSessionHasErrors('files.0');
});

test('store rejects files exceeding 20MB', function () {
    $file = UploadedFile::fake()->create('huge.pdf', 20481, 'application/pdf');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$file]])
        ->assertSessionHasErrors('files.0');
});

test('store accepts files up to 20MB', function () {
    $file = UploadedFile::fake()->create('large.pdf', 20480, 'application/pdf');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$file]])
        ->assertRedirect('/resume-upload');

    expect(Document::count())->toBe(1);
});

test('store sanitizes filenames with non-ASCII characters', function () {
    $file = UploadedFile::fake()->create('résumé—2024.pdf', 1024, 'application/pdf');

    $this->actingAs($this->user)
        ->post('/resume-upload', ['files' => [$file]])
        ->assertRedirect('/resume-upload');

    $document = Document::first();
    expect($document->filename)->toBe('resume-2024.pdf');
});

test('upload page shows previously uploaded documents', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'metadata' => ['purpose' => 'resume_import'],
    ]);

    $this->actingAs($this->user)
        ->get('/resume-upload')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('experience-library/upload')
            ->has('documents', 1)
        );
});

test('review page shows processing state', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);
    Cache::put("resume-parse:{$document->id}", ['status' => 'processing']);

    $this->actingAs($this->user)
        ->get("/resume-upload/{$document->id}/review")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/review-import')
                ->where('parseResult.status', 'processing')
        );
});

test('review page shows completed parse results', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);
    Cache::put("resume-parse:{$document->id}", [
        'status' => 'completed',
        'data' => [
            'experiences' => [['company' => 'Acme', 'title' => 'Dev', 'started_at' => '2023-01-01', 'is_current' => true]],
            'accomplishments' => [],
            'skills' => [['name' => 'PHP', 'category' => 'technical']],
            'education' => [],
            'projects' => [],
        ],
    ]);

    $this->actingAs($this->user)
        ->get("/resume-upload/{$document->id}/review")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('parseResult.status', 'completed')
                ->has('parseResult.data.experiences', 1)
                ->has('parseResult.data.skills', 1)
        );
});

test('review returns 403 for other users document', function () {
    $other = User::factory()->create();
    $document = Document::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/resume-upload/{$document->id}/review")
        ->assertForbidden();
});

test('commit imports selected data into library', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'experiences' => [
                ['company' => 'Acme Corp', 'title' => 'Engineer', 'started_at' => '2023-01-01', 'is_current' => true],
            ],
            'skills' => [
                ['name' => 'PHP', 'category' => 'technical'],
                ['name' => 'Laravel', 'category' => 'technical'],
            ],
            'accomplishments' => [
                ['title' => 'Built API', 'description' => 'Designed REST API', 'experience_index' => 0],
            ],
            'education' => [
                ['type' => 'degree', 'institution' => 'MIT', 'title' => 'CS Degree'],
            ],
            'projects' => [
                ['name' => 'Portal', 'description' => 'Customer portal', 'experience_index' => 0],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect(Experience::count())->toBe(1);
    expect(Skill::count())->toBe(2);
    expect($this->user->accomplishments()->count())->toBe(1);
    expect($this->user->educationEntries()->count())->toBe(1);
    expect($this->user->projects()->count())->toBe(1);
});

test('commit returns 403 for other users document', function () {
    $other = User::factory()->create();
    $document = Document::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [])
        ->assertForbidden();
});

test('commit imports URLs as link entries', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'urls' => [
                ['url' => 'https://github.com/jdoe', 'type' => 'github', 'label' => 'GitHub Profile'],
                ['url' => 'https://linkedin.com/in/jdoe', 'type' => 'linkedin'],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect(EvidenceEntry::count())->toBe(2);

    $github = EvidenceEntry::where('url', 'https://github.com/jdoe')->first();
    expect($github)
        ->type->toBe('repository')
        ->title->toBe('GitHub Profile');

    $linkedin = EvidenceEntry::where('url', 'https://linkedin.com/in/jdoe')->first();
    expect($linkedin)
        ->type->toBe('portfolio');
});

test('commit skips duplicate URLs', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);
    EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://github.com/jdoe',
    ]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'urls' => [
                ['url' => 'https://github.com/jdoe', 'type' => 'github'],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect(EvidenceEntry::where('user_id', $this->user->id)->count())->toBe(1);
});

test('commit auto-populates user linkedin_url from URL when empty', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'urls' => [
                ['url' => 'https://linkedin.com/in/jdoe', 'type' => 'linkedin'],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect($this->user->fresh()->linkedin_url)->toBe('https://linkedin.com/in/jdoe');
});

test('commit does not overwrite existing user linkedin_url', function () {
    $this->user->update(['linkedin_url' => 'https://linkedin.com/in/existing']);
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'urls' => [
                ['url' => 'https://linkedin.com/in/jdoe', 'type' => 'linkedin'],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect($this->user->fresh()->linkedin_url)->toBe('https://linkedin.com/in/existing');
});

test('commit auto-populates user portfolio_url from URL when empty', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resume-upload/{$document->id}/commit", [
            'urls' => [
                ['url' => 'https://johndoe.com', 'type' => 'portfolio'],
            ],
        ])
        ->assertRedirect('/experience-library');

    expect($this->user->fresh()->portfolio_url)->toBe('https://johndoe.com');
});
