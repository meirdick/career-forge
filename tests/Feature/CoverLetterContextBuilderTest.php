<?php

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\CoverLetterContextBuilder;

beforeEach(function () {
    $this->builder = new CoverLetterContextBuilder;
});

test('builds context with job posting', function () {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'raw_text' => 'We are looking for a senior developer.',
    ]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'job_posting_id' => $jobPosting->id,
        'company' => 'Acme Corp',
        'role' => 'Senior Developer',
    ]);

    $context = $this->builder->build($user, $application);

    expect($context)
        ->toContain('Jane Doe')
        ->toContain('jane@example.com')
        ->toContain('Acme Corp')
        ->toContain('Senior Developer')
        ->toContain('We are looking for a senior developer.');
});

test('builds context without job posting', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'job_posting_id' => null,
        'company' => 'Startup Inc',
        'role' => 'Engineer',
    ]);

    $context = $this->builder->build($user, $application);

    expect($context)
        ->toContain('Jane Doe')
        ->toContain('Startup Inc')
        ->toContain('Engineer')
        ->not->toContain('Job Posting:');
});

test('builds context without resume', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'resume_id' => null,
        'company' => 'BigCo',
        'role' => 'Manager',
    ]);

    $context = $this->builder->build($user, $application);

    expect($context)
        ->toContain('Jane Doe')
        ->toContain('BigCo')
        ->not->toContain('Resume:');
});
