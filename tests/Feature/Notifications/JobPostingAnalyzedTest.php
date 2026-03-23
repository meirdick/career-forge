<?php

use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\JobPostingAnalyzed;
use Illuminate\Notifications\Messages\MailMessage;

test('notification contains job title and company in subject', function () {
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
        ->and($mail->subject)->toContain('Acme Corp');
});

test('notification uses markdown template', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail->markdown)->toBe('mail.job-posting-analyzed');
});

test('notification passes job details to template', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create([
        'user_id' => $user->id,
        'title' => 'VP Engineering',
        'company' => 'TechCo',
        'location' => 'New York, NY',
        'seniority_level' => 'Vice President',
        'compensation' => '$200k-$300k',
        'remote_policy' => 'Hybrid',
    ]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail->viewData)
        ->toHaveKey('title', 'VP Engineering')
        ->toHaveKey('company', 'TechCo')
        ->toHaveKey('location', 'New York, NY')
        ->toHaveKey('seniority', 'Vice President')
        ->toHaveKey('compensation', '$200k-$300k')
        ->toHaveKey('remote', 'Hybrid');
});

test('notification passes profile data to template', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create(['user_id' => $user->id]);

    IdealCandidateProfile::factory()->create([
        'job_posting_id' => $jobPosting->id,
        'candidate_summary' => 'Ideal candidate has 5+ years of backend experience.',
        'experience_profile' => [
            'years_minimum' => 5,
            'industries' => ['SaaS', 'Fintech'],
        ],
        'cultural_fit' => [
            'values' => ['Innovation'],
            'work_style' => 'Agile',
        ],
        'red_flags' => [
            'No remote policy stated',
            'Vague compensation',
        ],
    ]);

    $notification = new JobPostingAnalyzed($jobPosting->fresh());
    $mail = $notification->toMail($user);

    expect($mail->viewData)
        ->toHaveKey('summary', 'Ideal candidate has 5+ years of backend experience.')
        ->and($mail->viewData['experience']['years_minimum'])->toBe(5)
        ->and($mail->viewData['culturalFit']['values'])->toContain('Innovation')
        ->and($mail->viewData['redFlags'])->toHaveCount(2);
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

    expect($mail->viewData)
        ->toHaveKey('summary', null)
        ->toHaveKey('experience', null)
        ->toHaveKey('culturalFit', null)
        ->and($mail->viewData['redFlags'])->toBe([]);
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

test('notification sets reply-to', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);

    $notification = new JobPostingAnalyzed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail->replyTo)->toContain(['careerforge@meirdick.com', 'CareerForge']);
});
