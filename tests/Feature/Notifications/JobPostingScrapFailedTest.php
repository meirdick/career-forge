<?php

use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\JobPostingScrapeFailed;
use Illuminate\Notifications\Messages\MailMessage;

test('notification contains url and action link', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/job/123',
    ]);

    $notification = new JobPostingScrapeFailed($jobPosting);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Could Not Be Scraped')
        ->and($mail->actionUrl)->toContain('/job-postings/');

    $introLines = collect($mail->introLines);
    expect($introLines->contains(fn ($line) => str_contains($line, 'example.com')))->toBeTrue();
});

test('notification includes custom reason', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/job/456',
    ]);

    $reason = 'The page only returned navigation boilerplate.';
    $notification = new JobPostingScrapeFailed($jobPosting, $reason);
    $mail = $notification->toMail($user);

    $introLines = collect($mail->introLines);
    expect($introLines->contains(fn ($line) => str_contains($line, 'boilerplate')))->toBeTrue();
});

test('notification toArray contains expected keys', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/job/789',
    ]);

    $notification = new JobPostingScrapeFailed($jobPosting, 'Custom reason.');
    $data = $notification->toArray($user);

    expect($data)->toHaveKeys(['job_posting_id', 'url', 'reason'])
        ->and($data['url'])->toBe('https://example.com/job/789')
        ->and($data['reason'])->toBe('Custom reason.');
});

test('notification uses mail channel', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);

    $notification = new JobPostingScrapeFailed($jobPosting);

    expect($notification->via($user))->toBe(['mail']);
});
