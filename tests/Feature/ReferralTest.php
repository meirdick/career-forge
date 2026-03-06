<?php

use App\Models\CreditBalance;
use App\Models\User;
use App\Services\CreditService;

test('new user gets a referral code on registration', function () {
    config(['ai.gating.signup_bonus' => 250]);

    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user->referral_code)->not->toBeNull()
        ->and(strlen($user->referral_code))->toBe(8);
});

test('registering with a referral code sets referred_by', function () {
    config(['ai.gating.signup_bonus' => 250, 'ai.gating.referral_bonus' => 250]);

    $referrer = User::factory()->create(['referral_code' => 'REFCODE1']);

    $this->post(route('register'), [
        'name' => 'Referred User',
        'email' => 'referred@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'referral_code' => 'REFCODE1',
    ]);

    $referred = User::where('email', 'referred@example.com')->first();

    expect($referred->referred_by)->toBe($referrer->id);
});

test('referrer receives bonus credits when someone uses their code', function () {
    config(['ai.gating.signup_bonus' => 250, 'ai.gating.referral_bonus' => 250]);

    $referrer = User::factory()->create(['referral_code' => 'REFCODE2']);
    CreditBalance::factory()->withBalance(250)->create(['user_id' => $referrer->id]);

    $this->post(route('register'), [
        'name' => 'Referred User',
        'email' => 'referred2@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'referral_code' => 'REFCODE2',
    ]);

    expect($referrer->fresh()->creditBalance->balance)->toBe(500);
});

test('referral bonus is idempotent', function () {
    config(['ai.gating.referral_bonus' => 250]);

    $referrer = User::factory()->create(['referral_code' => 'REFCODE3']);
    $referred = User::factory()->create(['referred_by' => $referrer->id]);

    $service = app(CreditService::class);

    $first = $service->grantReferralBonus($referrer, $referred);
    $second = $service->grantReferralBonus($referrer, $referred);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();
});

test('invalid referral code is ignored', function () {
    config(['ai.gating.signup_bonus' => 250]);

    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'noref@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'referral_code' => 'INVALID_CODE',
    ]);

    $user = User::where('email', 'noref@example.com')->first();

    expect($user->referred_by)->toBeNull();
});
