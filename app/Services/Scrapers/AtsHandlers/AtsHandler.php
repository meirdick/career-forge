<?php

namespace App\Services\Scrapers\AtsHandlers;

interface AtsHandler
{
    public function canHandle(string $url): bool;

    /**
     * @return array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}|null
     */
    public function extract(string $url): ?array;
}
