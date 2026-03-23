<?php

namespace App\Services\Scrapers;

use App\Contracts\WebScraperContract;
use App\Services\Scrapers\AtsHandlers\AshbyHandler;
use App\Services\Scrapers\AtsHandlers\AtsHandler;
use App\Services\Scrapers\AtsHandlers\GreenhouseHandler;
use App\Services\Scrapers\AtsHandlers\LeverHandler;
use App\Services\Scrapers\AtsHandlers\WorkdayHandler;
use Illuminate\Support\Facades\Log;

class AtsApiDriver implements WebScraperContract
{
    /** @var list<AtsHandler> */
    private array $handlers;

    /**
     * @param  list<AtsHandler>|null  $handlers
     */
    public function __construct(?array $handlers = null)
    {
        $this->handlers = $handlers ?? [
            new GreenhouseHandler,
            new LeverHandler,
            new AshbyHandler,
            new WorkdayHandler,
        ];
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        return null;
    }

    public function scrape(string $url): ?string
    {
        foreach ($this->handlers as $handler) {
            if (! $handler->canHandle($url)) {
                continue;
            }

            Log::info('ATS API handler matched', [
                'handler' => $handler::class,
                'url' => $url,
            ]);

            $data = $handler->extract($url);

            if ($data === null) {
                Log::warning('ATS API handler matched but extraction failed', [
                    'handler' => $handler::class,
                    'url' => $url,
                ]);

                return null;
            }

            $markdown = JobPostingMarkdownFormatter::format($data);

            if ($markdown === null) {
                Log::warning('ATS API handler returned data but formatter produced no output', [
                    'handler' => $handler::class,
                    'url' => $url,
                ]);

                return null;
            }

            return $markdown;
        }

        return null;
    }
}
