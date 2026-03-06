<?php

use App\Models\CreditBalance;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\PolarService;

beforeEach(function () {
    config(['ai.gating.mode' => 'gated']);
});

function mockPolarOrders(array $orders = []): void
{
    $mock = Mockery::mock(PolarService::class);
    $mock->shouldReceive('getOrdersForUser')->andReturn($orders);
    $mock->shouldReceive('createCheckout')->andReturn('https://polar.sh/checkout/test');
    app()->instance(PolarService::class, $mock);
}

test('billing page is displayed when gating enabled', function () {
    mockPolarOrders();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('billing.show'));

    $response->assertOk();
});

test('billing page shows credit balance', function () {
    mockPolarOrders();
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(250)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('billing.show'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/billing')
        ->where('balance', 250)
    );
});

test('billing page reconciles uncredited orders from polar', function () {
    mockPolarOrders([
        ['id' => 'polar-order-1', 'product_id' => 'prod-1', 'amount' => 500],
        ['id' => 'polar-order-2', 'product_id' => 'prod-1', 'amount' => 500],
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('billing.show'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('balance', 1000)
    );

    expect($user->creditTransactions()->count())->toBe(2);
});

test('billing page does not double-credit already reconciled orders', function () {
    mockPolarOrders([
        ['id' => 'polar-order-1', 'product_id' => 'prod-1', 'amount' => 500],
    ]);

    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(500)->create(['user_id' => $user->id]);
    CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'polar_order_id' => 'polar-order-1',
        'amount' => 500,
        'balance_after' => 500,
    ]);

    $response = $this->actingAs($user)
        ->get(route('billing.show'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('balance', 500)
    );

    expect($user->creditTransactions()->count())->toBe(1);
});

test('billing success page reconciles and shows purchase confirmation', function () {
    mockPolarOrders([
        ['id' => 'polar-order-new', 'product_id' => 'prod-1', 'amount' => 500],
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('billing.success', ['checkout_id' => 'test-checkout-id']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('purchaseSuccess', true)
        ->where('balance', 500)
    );
});

test('billing success page without checkout_id does not show purchase confirmation', function () {
    mockPolarOrders();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('billing.success'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('purchaseSuccess', false)
    );
});
