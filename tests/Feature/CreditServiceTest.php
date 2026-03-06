<?php

use App\Enums\CreditTransactionType;
use App\Models\User;
use App\Services\CreditService;

beforeEach(function () {
    config(['ai.gating.mode' => 'gated']);
});

test('signup bonus grants credits to new user', function () {
    config(['ai.gating.signup_bonus' => 250]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    $transaction = $service->grantSignupBonus($user);

    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(250);
    expect($transaction->type)->toBe(CreditTransactionType::Bonus);
    expect($transaction->description)->toBe('Signup bonus');
    expect($service->getBalance($user))->toBe(250);
});

test('signup bonus is not granted twice', function () {
    config(['ai.gating.signup_bonus' => 250]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    $service->grantSignupBonus($user);
    $second = $service->grantSignupBonus($user);

    expect($second)->toBeNull();
    expect($service->getBalance($user))->toBe(250);
});

test('signup bonus skipped when amount is zero', function () {
    config(['ai.gating.signup_bonus' => 0]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    expect($service->grantSignupBonus($user))->toBeNull();
    expect($service->getBalance($user))->toBe(0);
});

test('promo code redeems credits', function () {
    config(['ai.gating.promo_code' => 'LAUNCH500', 'ai.gating.promo_code_credits' => 500]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    $transaction = $service->redeemPromoCode($user, 'LAUNCH500');

    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(500);
    expect($transaction->type)->toBe(CreditTransactionType::Bonus);
    expect($transaction->metadata)->toBe(['promo_code' => 'LAUNCH500']);
    expect($service->getBalance($user))->toBe(500);
});

test('promo code is case insensitive', function () {
    config(['ai.gating.promo_code' => 'LAUNCH500', 'ai.gating.promo_code_credits' => 500]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    $transaction = $service->redeemPromoCode($user, 'launch500');

    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(500);
});

test('promo code cannot be redeemed twice', function () {
    config(['ai.gating.promo_code' => 'LAUNCH500', 'ai.gating.promo_code_credits' => 500]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    $service->redeemPromoCode($user, 'LAUNCH500');
    $second = $service->redeemPromoCode($user, 'LAUNCH500');

    expect($second)->toBeNull();
    expect($service->getBalance($user))->toBe(500);
});

test('invalid promo code returns null', function () {
    config(['ai.gating.promo_code' => 'LAUNCH500', 'ai.gating.promo_code_credits' => 500]);

    $user = User::factory()->create();
    $service = app(CreditService::class);

    expect($service->redeemPromoCode($user, 'FAKE123'))->toBeNull();
    expect($service->getBalance($user))->toBe(0);
});

test('registration grants signup bonus', function () {
    config(['ai.gating.signup_bonus' => 250]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();

    $service = app(CreditService::class);
    expect($service->getBalance($user))->toBe(250);
    expect($user->creditTransactions()->where('type', CreditTransactionType::Bonus)->count())->toBe(1);
});
