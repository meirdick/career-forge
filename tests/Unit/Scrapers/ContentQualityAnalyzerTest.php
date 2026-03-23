<?php

use App\Services\Scrapers\ContentQualityAnalyzer;

function realisticJobPosting(): string
{
    return <<<'MD'
    # Senior Software Engineer

    ## About Us

    Acme Corp is a leading technology company building innovative solutions for the modern workforce. Our team of 200+ engineers works on challenging problems at scale.

    ## Role Overview

    We are looking for a Senior Software Engineer to join our platform team. This position offers an exciting opportunity to shape the future of our core infrastructure.

    ## Responsibilities

    - Design and implement scalable microservices architecture
    - Lead code reviews and mentor junior engineers on the team
    - Collaborate with product managers to define technical requirements
    - Write comprehensive tests and maintain high code quality standards
    - Participate in on-call rotations and incident response procedures

    ## Requirements

    - 5+ years of professional software engineering experience
    - Strong proficiency in Python, Go, or similar languages
    - Experience with distributed systems and cloud platforms such as AWS or GCP
    - Excellent communication skills and ability to work in a collaborative environment
    - Bachelor's degree in Computer Science or equivalent practical experience

    ## Qualifications

    - Experience with Kubernetes and Docker containerization
    - Familiarity with CI/CD pipelines and infrastructure as code
    - Track record of shipping production systems at scale

    ## Benefits

    - Competitive salary range of $150,000 - $200,000 per year
    - Comprehensive health, dental, and vision insurance
    - 401k matching up to 4% of your annual salary
    - Unlimited PTO and flexible work arrangements
    - Annual learning and development budget of $2,500

    ## How to Apply

    Submit your resume and a brief cover letter explaining why you're interested in this role. We review applications on a rolling basis and aim to respond within one week.
    MD;
}

function shellOnlyContent(): string
{
    return <<<'MD'
    Skip to content
    Sign in
    Loading...
    © 2024 Workday, Inc. All rights reserved.
    Privacy Policy
    Terms of Use
    Cookie Preferences
    Loading application content...
    Please wait while we load the page.
    Sign in to continue
    Accept cookies to proceed
    MD;
}

test('valid job posting passes quality analysis', function () {
    $result = ContentQualityAnalyzer::analyze(realisticJobPosting());

    expect($result->isValid)->toBeTrue()
        ->and($result->score)->toBeGreaterThanOrEqual(4)
        ->and($result->maxScore)->toBe(6);
});

test('shell-only content fails quality analysis', function () {
    $result = ContentQualityAnalyzer::analyze(shellOnlyContent());

    expect($result->isValid)->toBeFalse()
        ->and($result->score)->toBeLessThan(4);
});

test('isJobPostingContent returns true for valid content', function () {
    expect(ContentQualityAnalyzer::isJobPostingContent(realisticJobPosting()))->toBeTrue();
});

test('isJobPostingContent returns false for shell content', function () {
    expect(ContentQualityAnalyzer::isJobPostingContent(shellOnlyContent()))->toBeFalse();
});

test('length signal requires at least 500 characters', function () {
    $short = str_repeat('word ', 90); // ~450 chars
    $result = ContentQualityAnalyzer::analyze($short);

    expect($result->signals['length'])->toBeFalse();

    $long = str_repeat('word ', 110); // ~550 chars
    $result = ContentQualityAnalyzer::analyze($long);

    expect($result->signals['length'])->toBeTrue();
});

test('unique_words signal requires at least 50 unique words', function () {
    $repetitive = str_repeat('hello world test. ', 50);
    $result = ContentQualityAnalyzer::analyze($repetitive);

    expect($result->signals['unique_words'])->toBeFalse();
});

test('job_keywords signal requires at least 2 job-related keywords', function () {
    $noKeywords = str_repeat('The quick brown fox jumps over the lazy dog. ', 20);
    $result = ContentQualityAnalyzer::analyze($noKeywords);

    expect($result->signals['job_keywords'])->toBeFalse();

    $withKeywords = 'Responsibilities include managing the team. Requirements are 5 years experience. Qualifications preferred.';
    $result = ContentQualityAnalyzer::analyze($withKeywords);

    expect($result->signals['job_keywords'])->toBeTrue();
});

test('low_boilerplate signal detects boilerplate-heavy content', function () {
    $result = ContentQualityAnalyzer::analyze(shellOnlyContent());

    expect($result->signals['low_boilerplate'])->toBeFalse();
});

test('has_headings signal requires at least 2 markdown headings', function () {
    $noHeadings = str_repeat('Some paragraph text here. ', 30);
    $result = ContentQualityAnalyzer::analyze($noHeadings);

    expect($result->signals['has_headings'])->toBeFalse();

    $withHeadings = "# Title\n\n## Section One\n\nSome content here.\n\n## Section Two\n\nMore content.";
    $result = ContentQualityAnalyzer::analyze($withHeadings);

    expect($result->signals['has_headings'])->toBeTrue();
});

test('has_sentences signal requires at least 3 sentences of 20+ characters', function () {
    $fragments = 'short. tiny. small.';
    $result = ContentQualityAnalyzer::analyze($fragments);

    expect($result->signals['has_sentences'])->toBeFalse();

    $withSentences = 'This is a sufficiently long sentence that counts. Here is another sentence that also meets the length requirement. And a third sentence to satisfy the minimum count.';
    $result = ContentQualityAnalyzer::analyze($withSentences);

    expect($result->signals['has_sentences'])->toBeTrue();
});

test('result contains all six signal keys', function () {
    $result = ContentQualityAnalyzer::analyze('test');

    expect($result->signals)->toHaveKeys([
        'length',
        'unique_words',
        'job_keywords',
        'low_boilerplate',
        'has_headings',
        'has_sentences',
    ]);
});

test('empty content fails all signals', function () {
    $result = ContentQualityAnalyzer::analyze('');

    expect($result->isValid)->toBeFalse()
        ->and($result->score)->toBe(0);
});

test('borderline content with 563 chars of shell fails', function () {
    // Simulates the actual Workday scrape failure
    $shellContent = <<<'MD'
    Skip to content

    Sign in

    Loading...

    Workday

    Senior Director, Lead Product Manager - Norton 360

    Loading...

    © Copyright 2024 Workday, Inc. All rights reserved.

    Privacy

    Terms of Use

    Cookie Preferences

    Powered by Workday

    We use cookies to ensure you get the best experience. By continuing to browse you agree to our cookie policy.

    Accept All Cookies

    Manage Preferences

    Back to Top

    Loading application content, please wait...

    If this page does not load, please refresh your browser.
    MD;

    $result = ContentQualityAnalyzer::analyze($shellContent);

    expect($result->isValid)->toBeFalse()
        ->and(mb_strlen(trim($shellContent)))->toBeGreaterThan(200);
});
