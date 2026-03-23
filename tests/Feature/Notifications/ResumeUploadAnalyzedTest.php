<?php

use App\Models\Document;
use App\Models\User;
use App\Notifications\ResumeUploadAnalyzed;
use App\Notifications\ResumeUploadFailed;
use Illuminate\Notifications\Messages\MailMessage;

test('success notification contains filename in subject', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create([
        'user_id' => $user->id,
        'filename' => 'MyResume.pdf',
    ]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('Resume Parsed: MyResume.pdf');
});

test('success notification uses markdown template', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail->markdown)->toBe('mail.resume-upload-analyzed');
});

test('success notification passes extraction counts to template', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail->viewData['counts'])
        ->toHaveKey('experiences', 2)
        ->toHaveKey('skills', 4)
        ->toHaveKey('accomplishments', 1)
        ->toHaveKey('education', 1)
        ->toHaveKey('projects', 0);
});

test('success notification passes latest role and top skills', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail->viewData)
        ->toHaveKey('latestRole', 'Senior Engineer')
        ->toHaveKey('latestCompany', 'Acme Corp')
        ->and($mail->viewData['topSkills'])->toContain('PHP', 'Laravel', 'React');
});

test('success notification handles empty parsed data gracefully', function () {
    $user = User::factory()->create();
    $document = Document::factory()->resumeImport()->create([
        'user_id' => $user->id,
        'parsed_data' => [],
    ]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail->viewData['counts']['experiences'])->toBe(0)
        ->and($mail->viewData['latestRole'])->toBeNull()
        ->and($mail->viewData['topSkills'])->toBe([]);
});

test('success notification sets reply-to', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadAnalyzed($document);
    $mail = $notification->toMail($user);

    expect($mail->replyTo)->toContain(['careerforge@meirdick.com', 'CareerForge']);
});

test('success notification uses mail channel', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadAnalyzed($document);

    expect($notification->via($user))->toBe(['mail']);
});

test('success notification toArray contains expected keys', function () {
    $user = User::factory()->create();
    $document = Document::factory()->parsed()->create([
        'user_id' => $user->id,
        'filename' => 'Resume.pdf',
    ]);

    $notification = new ResumeUploadAnalyzed($document);
    $data = $notification->toArray($user);

    expect($data)->toHaveKeys(['document_id', 'filename'])
        ->and($data['document_id'])->toBe($document->id)
        ->and($data['filename'])->toBe('Resume.pdf');
});

test('failure notification contains filename in subject', function () {
    $user = User::factory()->create();
    $document = Document::factory()->resumeImport()->create([
        'user_id' => $user->id,
        'filename' => 'BadResume.pdf',
    ]);

    $notification = new ResumeUploadFailed($document);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Resume Parse Issue: BadResume.pdf');
});

test('failure notification uses markdown template', function () {
    $user = User::factory()->create();
    $document = Document::factory()->resumeImport()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadFailed($document);
    $mail = $notification->toMail($user);

    expect($mail->markdown)->toBe('mail.resume-upload-failed');
});

test('failure notification sets reply-to', function () {
    $user = User::factory()->create();
    $document = Document::factory()->resumeImport()->create(['user_id' => $user->id]);

    $notification = new ResumeUploadFailed($document);
    $mail = $notification->toMail($user);

    expect($mail->replyTo)->toContain(['careerforge@meirdick.com', 'CareerForge']);
});
