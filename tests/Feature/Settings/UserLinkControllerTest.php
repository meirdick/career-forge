<?php

use App\Models\User;
use App\Models\UserLink;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('store creates a user link', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [
            'url' => 'https://example.com',
            'label' => 'My Site',
            'type' => 'portfolio',
        ])
        ->assertRedirect();

    expect(UserLink::first())
        ->url->toBe('https://example.com')
        ->label->toBe('My Site')
        ->type->toBe('portfolio')
        ->user_id->toBe($this->user->id);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [])
        ->assertSessionHasErrors(['url', 'type']);
});

test('store validates url format', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [
            'url' => 'not a valid url with spaces',
            'type' => 'portfolio',
        ])
        ->assertSessionHasErrors(['url']);
});

test('store validates type enum', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [
            'url' => 'https://example.com',
            'type' => 'invalid',
        ])
        ->assertSessionHasErrors(['type']);
});

test('store label is optional', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [
            'url' => 'https://example.com',
            'type' => 'github',
        ])
        ->assertRedirect();

    expect(UserLink::first())
        ->label->toBeNull()
        ->type->toBe('github');
});

test('update modifies a user link', function () {
    $link = UserLink::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put(route('user-links.update', $link), [
            'url' => 'https://updated.com',
            'label' => 'Updated',
            'type' => 'website',
        ])
        ->assertRedirect();

    expect($link->fresh())
        ->url->toBe('https://updated.com')
        ->label->toBe('Updated')
        ->type->toBe('website');
});

test('update returns 403 for other users link', function () {
    $other = User::factory()->create();
    $link = UserLink::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put(route('user-links.update', $link), [
            'url' => 'https://hacked.com',
            'type' => 'portfolio',
        ])
        ->assertForbidden();
});

test('destroy deletes a user link', function () {
    $link = UserLink::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete(route('user-links.destroy', $link))
        ->assertRedirect();

    expect(UserLink::find($link->id))->toBeNull();
});

test('destroy returns 403 for other users link', function () {
    $other = User::factory()->create();
    $link = UserLink::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete(route('user-links.destroy', $link))
        ->assertForbidden();
});

test('reorder updates sort order', function () {
    $links = UserLink::factory()->count(3)->create(['user_id' => $this->user->id]);

    $order = [$links[2]->id, $links[0]->id, $links[1]->id];

    $this->actingAs($this->user)
        ->post(route('user-links.reorder'), ['order' => $order])
        ->assertRedirect();

    expect($links[2]->fresh()->sort_order)->toBe(0);
    expect($links[0]->fresh()->sort_order)->toBe(1);
    expect($links[1]->fresh()->sort_order)->toBe(2);
});

test('store normalizes url without protocol', function () {
    $this->actingAs($this->user)
        ->post(route('user-links.store'), [
            'url' => 'www.example.com',
            'type' => 'website',
        ])
        ->assertRedirect();

    expect(UserLink::first()->url)->toBe('https://www.example.com');
});
