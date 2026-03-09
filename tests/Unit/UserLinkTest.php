<?php

use App\Models\UserLink;

test('displayUrl returns label when set', function () {
    $link = new UserLink(['url' => 'https://example.com', 'label' => 'My Portfolio']);

    expect($link->displayUrl())->toBe('My Portfolio');
});

test('displayUrl extracts domain when no label', function () {
    $link = new UserLink(['url' => 'https://example.com/projects', 'label' => null]);

    expect($link->displayUrl())->toBe('example.com');
});

test('displayUrl strips www prefix', function () {
    $link = new UserLink(['url' => 'https://www.example.com', 'label' => null]);

    expect($link->displayUrl())->toBe('example.com');
});

test('displayUrl falls back to raw url for invalid urls', function () {
    $link = new UserLink(['url' => 'not-a-url', 'label' => null]);

    expect($link->displayUrl())->toBe('not-a-url');
});
