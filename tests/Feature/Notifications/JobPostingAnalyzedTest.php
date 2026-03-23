<?php

use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\JobPostingAnalyzed;
use Illuminate\Notifications\Messages\MailMessage;

test('notification contains job title, company, and link', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'title' => 'Senior Engineer',
        'company' => 'Acme Corp',
        'location' => 'San Francisco, CA',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Senior Engineer')
        ->and($mail->subject)->toContain('Acme Corp')
        ->and($mail->actionUrl)->toContain('/job-postings/');
});

test('notification includes candidate summary when available', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
        'title' => 'Backend Developer',
        'company' => 'TechCo',
    ]);

    IdealCandidateProfile::factory()->create([
        'job_posting_id' => $jobPosting->id,
        'candidate_summary' => 'Ideal candidate has 5+ years of backend experience.',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting->fresh());
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines);
    expect($introLines->contains(fn ($line) => str_contains($line, '5+ years')))->toBeTrue();
});

test('notification includes required skills', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
    ]);

    IdealCandidateProfile::factory()->create([
        'job_posting_id' => $jobPosting->id,
        'required_skills' => [
            ['name' => 'PHP', 'years' => 5],
            ['name' => 'Laravel', 'years' => 3],
        ],
    ]);

    $notification = new JobPostingAnalyzed($jobPosting->fresh());
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines)->implode(' ');
    expect($introLines)
        ->toContain('PHP (5+ yrs)')
        ->toContain('Laravel (3+ yrs)')
        ->toContain('Must-have skills');
});

test('notification includes preferred skills', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
    ]);

    IdealCandidateProfile::factory()->create([
        'job_posting_id' => $jobPosting->id,
        'preferred_skills' => [
            ['name' => 'React', 'years' => 2],
            ['name' => 'Docker', 'years' => 1],
        ],
    ]);

    $notification = new JobPostingAnalyzed($jobPosting->fresh());
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines)->implode(' ');
    expect($introLines)
        ->toContain('React')
        ->toContain('Docker')
        ->toContain('Nice-to-have');
});

test('notification includes red flags', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
    ]);

    IdealCandidateProfile::factory()->create([
        'job_posting_id' => $jobPosting->id,
        'red_flags' => [
            'Job hopping without growth',
            'No version control experience',
        ],
    ]);

    $notification = new JobPostingAnalyzed($jobPosting->fresh());
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines)->implode(' ');
    expect($introLines)
        ->toContain('Red flags to avoid')
        ->toContain('Job hopping without growth');
});

test('notification includes job details line', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
        'seniority_level' => 'Senior',
        'remote_policy' => 'Hybrid',
        'compensation' => '$130k-$170k',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines)->implode(' ');
    expect($introLines)
        ->toContain('Senior')
        ->toContain('Hybrid')
        ->toContain('$130k-$170k');
});

test('notification handles missing profile gracefully', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'title' => 'Designer',
        'company' => 'DesignCo',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Designer');
});

test('notification toArray contains expected keys', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'title' => 'Designer',
        'company' => 'DesignCo',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $data = $notification->toArray($user);

    expect($data)->toHaveKeys(['job_posting_id', 'title', 'company'])
        ->and($data['title'])->toBe('Designer')
        ->and($data['company'])->toBe('DesignCo');
});

test('notification uses mail channel', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);

    $notification = new JobPostingAnalyzed($jobPosting);

    expect($notification->via($user))->toBe(['mail']);
});
