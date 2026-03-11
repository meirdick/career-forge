<?php

namespace App\Contracts;

interface WebScraperContract
{
    public function isConfigured(): bool;

    /**
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array;

    public function scrape(string $url): ?string;
}
