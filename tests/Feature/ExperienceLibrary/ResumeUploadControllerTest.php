<?php

use App\Jobs\ParseResumeJob;
use App\Models\Document;
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
