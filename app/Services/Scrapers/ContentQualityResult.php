<?php

namespace App\Services\Scrapers;

readonly class ContentQualityResult
{
    /**
     * @param  array<string, bool>  $signals
     */
    public function __construct(
        public bool $isValid,
        public int $score,
        public int $maxScore,
        public array $signals,
    ) {}
}
