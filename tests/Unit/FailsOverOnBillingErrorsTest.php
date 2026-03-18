<?php

use App\Ai\Concerns\FailsOverOnBillingErrors;
use Laravel\Ai\Exceptions\AiException;

it('identifies transient server errors by HTTP status code', function () {
    $trait = new class
    {
        use FailsOverOnBillingErrors;

        public function instructions(): string
        {
            return '';
        }

        public function testIsTransientServerError(AiException $e): bool
        {
            return (new \ReflectionMethod($this, 'isTransientServerError'))->invoke($this, $e);
        }
    };

    // 500 Internal Server Error
    expect($trait->testIsTransientServerError(new AiException('Unknown error', 500)))->toBeTrue();

    // 502 Bad Gateway
    expect($trait->testIsTransientServerError(new AiException('Bad Gateway', 502)))->toBeTrue();

    // 503 Service Unavailable
    expect($trait->testIsTransientServerError(new AiException('Service Unavailable', 503)))->toBeTrue();

    // 400 is NOT a server error
    expect($trait->testIsTransientServerError(new AiException('Bad Request', 400)))->toBeFalse();

    // 429 is NOT a server error (handled by RateLimitedException)
    expect($trait->testIsTransientServerError(new AiException('Rate limited', 429)))->toBeFalse();

    // 0 (no code) is NOT a server error
    expect($trait->testIsTransientServerError(new AiException('Some error', 0)))->toBeFalse();
});

it('identifies billing errors by message content', function () {
    $trait = new class
    {
        use FailsOverOnBillingErrors;

        public function instructions(): string
        {
            return '';
        }

        public function testIsBillingError(AiException $e): bool
        {
            return (new \ReflectionMethod($this, 'isBillingError'))->invoke($this, $e);
        }
    };

    expect($trait->testIsBillingError(new AiException('Your credit balance is too low')))->toBeTrue();
    expect($trait->testIsBillingError(new AiException('Insufficient funds')))->toBeTrue();
    expect($trait->testIsBillingError(new AiException('Billing issue detected')))->toBeTrue();
    expect($trait->testIsBillingError(new AiException('Quota exceeded')))->toBeTrue();
    expect($trait->testIsBillingError(new AiException('Unknown error', 500)))->toBeFalse();
});

it('identifies token limit errors by message content', function () {
    $trait = new class
    {
        use FailsOverOnBillingErrors;

        public function instructions(): string
        {
            return '';
        }

        public function testIsTokenLimitError(AiException $e): bool
        {
            return (new \ReflectionMethod($this, 'isTokenLimitError'))->invoke($this, $e);
        }
    };

    expect($trait->testIsTokenLimitError(new AiException('Gemini hit token limit with high thinking')))->toBeTrue();
    expect($trait->testIsTokenLimitError(new AiException('The output was truncated at 82338 characters')))->toBeTrue();
    expect($trait->testIsTokenLimitError(new AiException('Exceeded max_tokens')))->toBeTrue();
    expect($trait->testIsTokenLimitError(new AiException('Unknown error', 500)))->toBeFalse();
});
