<?php

use App\Jobs\AnalyzeJobPostingJob;
use App\Jobs\FetchJobPostingUrlJob;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    Queue::fake();
});

test('guest cannot access job posting pages', function () {
    $this->get('/job-postings')->assertRedirect('/login');
    $this->post('/job-postings')->assertRedirect('/login');
});

test('index displays job postings', function () {
    JobPosting::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/job-postings')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('job-postings/index')
                ->has('postings', 3)
        );
});

test('create page renders', function () {
    $this->actingAs($this->user)
        ->get('/job-postings/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('job-postings/create'));
});

test('store creates posting and dispatches analysis job', function () {
    $this->actingAs($this->user)->post('/job-postings', [
        'raw_text' => 'We are looking for a Senior Software Engineer...',
        'title' => 'Senior Engineer',
        'company' => 'Acme Corp',
    ])->assertRedirect();

    $posting = JobPosting::first();
    expect($posting)
        ->raw_text->toContain('Senior Software Engineer')
        ->user_id->toBe($this->user->id);

    Queue::assertPushed(AnalyzeJobPostingJob::class, function ($job) use ($posting) {
        return $job->jobPosting->id === $posting->id;
    });
});

test('store validates raw_text is required when no url', function () {
    $this->actingAs($this->user)
        ->post('/job-postings', [])
        ->assertSessionHasErrors('raw_text');
});

test('store with url and no raw_text dispatches fetch job', function () {
    $this->actingAs($this->user)->post('/job-postings', [
        'url' => 'https://example.com/jobs/123',
        'title' => 'Remote Engineer',
        'company' => 'Test Co',
    ])->assertRedirect();

    $posting = JobPosting::first();
    expect($posting)
        ->url->toBe('https://example.com/jobs/123')
        ->raw_text->toBeNull();

    Queue::assertPushed(FetchJobPostingUrlJob::class, function ($job) use ($posting) {
        return $job->jobPosting->id === $posting->id;
    });
    Queue::assertNotPushed(AnalyzeJobPostingJob::class);
});

test('store with url and raw_text dispatches analyze job directly', function () {
    $this->actingAs($this->user)->post('/job-postings', [
        'url' => 'https://example.com/jobs/456',
        'raw_text' => 'We need a great engineer...',
        'title' => 'Engineer',
    ])->assertRedirect();

    Queue::assertNotPushed(FetchJobPostingUrlJob::class);
    Queue::assertPushed(AnalyzeJobPostingJob::class);
});

test('show displays posting with profile', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $this->actingAs($this->user)
        ->get("/job-postings/{$posting->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('job-postings/show')
                ->has('posting.ideal_candidate_profile')
        );
});

test('show returns 403 for other users posting', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/job-postings/{$posting->id}")
        ->assertForbidden();
});

test('edit page renders', function () {
    $posting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/job-postings/{$posting->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('job-postings/edit'));
});

test('update modifies posting', function () {
    $posting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/job-postings/{$posting->id}", [
            'raw_text' => 'Updated posting text',
            'title' => 'Updated Title',
        ])
        ->assertRedirect("/job-postings/{$posting->id}");

    expect($posting->fresh())
        ->raw_text->toBe('Updated posting text')
        ->title->toBe('Updated Title');
});

test('destroy deletes posting', function () {
    $posting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/job-postings/{$posting->id}")
        ->assertRedirect('/job-postings');

    expect(JobPosting::find($posting->id))->toBeNull();
});

test('reanalyze dispatches new analysis job', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/job-postings/{$posting->id}/reanalyze")
        ->assertRedirect("/job-postings/{$posting->id}");

    expect($posting->fresh()->analyzed_at)->toBeNull();
    Queue::assertPushed(AnalyzeJobPostingJob::class);
});

test('reanalyze returns 403 for other users posting', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/job-postings/{$posting->id}/reanalyze")
        ->assertForbidden();
});

test('update profile sets is_user_edited and updates data', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $this->actingAs($this->user)
        ->put("/job-postings/{$posting->id}/profile", [
            'red_flags' => ['No remote', 'Unrealistic deadlines'],
        ])
        ->assertRedirect();

    expect($profile->fresh())
        ->is_user_edited->toBeTrue()
        ->red_flags->toHaveCount(2);
});

test('update profile returns 403 for other users posting', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $other->id]);
    IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $this->actingAs($this->user)
        ->put("/job-postings/{$posting->id}/profile", ['red_flags' => []])
        ->assertForbidden();
});

// Quick Store

test('quick store creates posting and dispatches fetch job', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/quick', ['url' => 'https://example.com/jobs/123'])
        ->assertRedirect('/job-postings');

    $posting = JobPosting::first();
    expect($posting)
        ->url->toBe('https://example.com/jobs/123')
        ->user_id->toBe($this->user->id);

    Queue::assertPushed(FetchJobPostingUrlJob::class, function ($job) use ($posting) {
        return $job->jobPosting->id === $posting->id;
    });
});

test('quick store validates url is required', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/quick', [])
        ->assertSessionHasErrors('url');
});

test('quick store validates url format', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/quick', ['url' => 'not-a-url'])
        ->assertSessionHasErrors('url');
});

test('guest cannot quick store', function () {
    $this->post('/job-postings/quick', ['url' => 'https://example.com'])
        ->assertRedirect('/login');
});

// Bulk Store

test('bulk store creates multiple postings and dispatches fetch jobs', function () {
    $urls = [
        'https://example.com/jobs/1',
        'https://example.com/jobs/2',
        'https://example.com/jobs/3',
    ];

    $this->actingAs($this->user)
        ->post('/job-postings/bulk', ['urls' => $urls])
        ->assertRedirect('/job-postings');

    expect(JobPosting::count())->toBe(3);
    Queue::assertPushed(FetchJobPostingUrlJob::class, 3);
});

test('bulk store validates urls are required', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/bulk', [])
        ->assertSessionHasErrors('urls');
});

test('bulk store validates max 20 urls', function () {
    $urls = array_map(fn ($i) => "https://example.com/jobs/{$i}", range(1, 21));

    $this->actingAs($this->user)
        ->post('/job-postings/bulk', ['urls' => $urls])
        ->assertSessionHasErrors('urls');

    expect(JobPosting::count())->toBe(0);
});

test('bulk store validates each url format', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/bulk', ['urls' => ['not-a-url', 'https://example.com/jobs/1']])
        ->assertSessionHasErrors('urls.0');

    expect(JobPosting::count())->toBe(0);
});

test('bulk store rejects duplicate urls', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/bulk', ['urls' => ['https://example.com/jobs/1', 'https://example.com/jobs/1']])
        ->assertSessionHasErrors('urls.0');

    expect(JobPosting::count())->toBe(0);
});

test('guest cannot bulk store', function () {
    $this->post('/job-postings/bulk', ['urls' => ['https://example.com']])
        ->assertRedirect('/login');
});

// Unsupported URL (LinkedIn) validation

test('quick store rejects linkedin urls', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/quick', ['url' => 'https://www.linkedin.com/jobs/view/123'])
        ->assertSessionHasErrors('url');

    expect(JobPosting::count())->toBe(0);
});

test('bulk store rejects linkedin urls', function () {
    $this->actingAs($this->user)
        ->post('/job-postings/bulk', ['urls' => [
            'https://www.linkedin.com/jobs/view/123',
            'https://example.com/jobs/1',
        ]])
        ->assertSessionHasErrors('urls.0');

    expect(JobPosting::count())->toBe(0);
});

test('store rejects linkedin url when no raw_text provided', function () {
    $this->actingAs($this->user)
        ->post('/job-postings', [
            'url' => 'https://www.linkedin.com/jobs/view/123',
        ])
        ->assertSessionHasErrors('url');

    expect(JobPosting::count())->toBe(0);
});

test('store allows linkedin url when raw_text is provided', function () {
    $this->actingAs($this->user)
        ->post('/job-postings', [
            'url' => 'https://www.linkedin.com/jobs/view/123',
            'raw_text' => 'We are looking for a Senior Engineer...',
            'title' => 'Senior Engineer',
        ])
        ->assertRedirect();

    $posting = JobPosting::first();
    expect($posting)
        ->url->toBe('https://www.linkedin.com/jobs/view/123')
        ->raw_text->toContain('Senior Engineer');

    Queue::assertPushed(AnalyzeJobPostingJob::class);
    Queue::assertNotPushed(FetchJobPostingUrlJob::class);
});
