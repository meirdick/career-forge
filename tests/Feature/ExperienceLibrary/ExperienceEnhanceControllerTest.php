<?php

use App\Ai\Agents\ContentEnhancer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('enhance returns enhanced content for experience', function () {
    ContentEnhancer::fake();

    $this->actingAs($this->user)
        ->postJson('/experience-library/enhance', [
            'section' => 'experience',
            'item' => [
                'title' => 'Developer',
                'description' => 'Wrote code',
                'location' => 'NYC',
            ],
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['title']);

    ContentEnhancer::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Developer') && str_contains($prompt->prompt, 'Wrote code');
    });
});

test('enhance returns enhanced content for accomplishment', function () {
    ContentEnhancer::fake();

    $this->actingAs($this->user)
        ->postJson('/experience-library/enhance', [
            'section' => 'accomplishment',
            'item' => [
                'title' => 'Built feature',
                'description' => 'Made a thing',
            ],
        ])
        ->assertSuccessful();
});

test('enhance validates section is required', function () {
    $this->actingAs($this->user)
        ->postJson('/experience-library/enhance', [
            'item' => ['title' => 'test'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('section');
});

test('enhance validates section value', function () {
    $this->actingAs($this->user)
        ->postJson('/experience-library/enhance', [
            'section' => 'invalid',
            'item' => ['title' => 'test'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('section');
});

test('enhance validates item is required', function () {
    $this->actingAs($this->user)
        ->postJson('/experience-library/enhance', [
            'section' => 'experience',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('item');
});

test('enhance requires authentication', function () {
    $this->postJson('/experience-library/enhance', [
        'section' => 'experience',
        'item' => ['title' => 'test'],
    ])->assertUnauthorized();
});
