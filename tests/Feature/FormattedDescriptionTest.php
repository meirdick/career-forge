<?php

use App\Models\Accomplishment;
use App\Models\Experience;
use App\Models\Project;

test('experience formatted_description sanitizes HTML tags', function () {
    $experience = Experience::factory()->create([
        'description' => '<script>alert("xss")</script><b>Bold text</b>',
    ]);

    expect($experience->formatted_description)
        ->not->toContain('<script>')
        ->toContain('<b>Bold text</b>');
});

test('experience formatted_description converts markdown to HTML', function () {
    $experience = Experience::factory()->create([
        'description' => "**bold** and *italic*\n\n- item one\n- item two",
    ]);

    expect($experience->formatted_description)
        ->toContain('<strong>bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<li>');
});

test('experience formatted_description handles null', function () {
    $experience = Experience::factory()->create(['description' => null]);

    expect($experience->formatted_description)->not->toBeNull();
});

test('accomplishment formatted_description sanitizes HTML tags', function () {
    $accomplishment = Accomplishment::factory()->create([
        'description' => '<img src=x onerror=alert(1)><p>Safe content</p>',
    ]);

    expect($accomplishment->formatted_description)
        ->not->toContain('<img')
        ->not->toContain('onerror')
        ->toContain('<p>Safe content</p>');
});

test('accomplishment formatted_description converts markdown', function () {
    $accomplishment = Accomplishment::factory()->create([
        'description' => '**Increased revenue** by 50%',
    ]);

    expect($accomplishment->formatted_description)
        ->toContain('<strong>Increased revenue</strong>');
});

test('project formatted_description sanitizes HTML tags', function () {
    $project = Project::factory()->create([
        'description' => '<div onclick="evil()"><strong>Good</strong> content</div>',
    ]);

    expect($project->formatted_description)
        ->not->toContain('<div')
        ->not->toContain('onclick')
        ->toContain('<strong>Good</strong>');
});

test('project formatted_description converts markdown', function () {
    $project = Project::factory()->create([
        'description' => "Built a **REST API** with:\n\n1. Authentication\n2. Rate limiting",
    ]);

    expect($project->formatted_description)
        ->toContain('<strong>REST API</strong>')
        ->toContain('<li>');
});

test('formatted_description is appended to JSON', function () {
    $experience = Experience::factory()->create(['description' => '**test**']);

    $json = $experience->toArray();

    expect($json)->toHaveKey('formatted_description');
});
