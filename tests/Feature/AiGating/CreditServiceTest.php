<?php

use App\Enums\AiPurpose;
use App\Enums\CreditTransactionType;
use App\Models\CreditBalance;
use App\Models\User;
use App\Services\CreditService;

test('purchaseCredits creates balance and transaction', function () {
    $user = User::factory()->create();
    $service = app(CreditService::class);

    $transaction = $service->purchaseCredits($user, 500, 'polar-order-123');

    expect($transaction->type)->toBe(CreditTransactionType::Purchase);
    expect($transaction->amount)->toBe(500);
    expect($transaction->balance_after)->toBe(500);
    expect($transaction->polar_order_id)->toBe('polar-order-123');

    $user->refresh();
    expect($user->creditBalance->balance)->toBe(500);
    expect($user->creditBalance->lifetime_purchased)->toBe(500);
});

test('purchaseCredits adds to existing balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(200)->create(['user_id' => $user->id]);

    $service = app(CreditService::class);
    $transaction = $service->purchaseCredits($user, 500);

    expect($transaction->balance_after)->toBe(700);
});

test('consumeCredits deducts from balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $user->id]);

    $service = app(CreditService::class);
    $transaction = $service->consumeCredits($user, 10, 'AI usage: chat_message');

    expect($transaction->type)->toBe(CreditTransactionType::Consumption);
    expect($transaction->amount)->toBe(-10);
    expect($transaction->balance_after)->toBe(90);

    $user->refresh();
    expect($user->creditBalance->balance)->toBe(90);
    expect($user->creditBalance->lifetime_consumed)->toBe(10);
});

test('consumeCredits throws when insufficient balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(5)->create(['user_id' => $user->id]);

    $service = app(CreditService::class);

    expect(fn () => $service->consumeCredits($user, 10, 'test'))
        ->toThrow(RuntimeException::class, 'Insufficient credits');
});

test('consumeCredits throws when no balance record', function () {
    $user = User::factory()->create();

    $service = app(CreditService::class);

    expect(fn () => $service->consumeCredits($user, 10, 'test'))
        ->toThrow(RuntimeException::class, 'Insufficient credits');
});

test('getBalance returns zero for users without balance', function () {
    $user = User::factory()->create();
    $service = app(CreditService::class);

    expect($service->getBalance($user))->toBe(0);
});

test('getBalance returns correct balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(42)->create(['user_id' => $user->id]);

    $service = app(CreditService::class);

    expect($service->getBalance($user))->toBe(42);
});

test('getCostForPurpose returns configured cost', function () {
    $service = app(CreditService::class);

    expect($service->getCostForPurpose(AiPurpose::ChatMessage))->toBe(2);
    expect($service->getCostForPurpose(AiPurpose::ResumeGeneration))->toBe(100);
    expect($service->getCostForPurpose(AiPurpose::JobAnalysis))->toBe(50);
});
