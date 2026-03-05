<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('interview route redirects to career chat', function () {
    $this->actingAs($this->user)
        ->get('/interview')
        ->assertRedirect('/career-chat');
});
