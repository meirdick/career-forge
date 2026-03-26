<?php

namespace App\Support;

class ParseQualityResult
{
    public function __construct(
        public readonly float $score,
        public readonly bool $passed,
        public readonly array $failedRules,
        public readonly string $retryHint,
        public readonly bool $inputTooShort,
    ) {}
}
