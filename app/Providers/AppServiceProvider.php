<?php

namespace App\Providers;

use App\Services\Scrapers\AtsApiDriver;
use App\Services\Scrapers\CloudflareScraperDriver;
use App\Services\Scrapers\FirecrawlScraperDriver;
use App\Services\Scrapers\JsonLdScraperDriver;
use App\Services\WebScraperService;
use Carbon\CarbonImmutable;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Psr\Http\Message\ResponseInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebScraperService::class, fn ($app) => new WebScraperService([
            $app->make(AtsApiDriver::class),
            $app->make(JsonLdScraperDriver::class),
            $app->make(CloudflareScraperDriver::class),
            $app->make(FirecrawlScraperDriver::class),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->normalizeWorkersAiResponses();
    }

    /**
     * Workers AI returns structured output `content` as a JSON object instead
     * of a string. Prism's XAI handler expects a string in `content` and the
     * parsed object in `parsed`. This middleware normalizes the response.
     */
    protected function normalizeWorkersAiResponses(): void
    {
        Http::globalResponseMiddleware(function (ResponseInterface $response): ResponseInterface {
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (! is_array($data) || ! isset($data['choices']) || ! is_array($data['choices'])) {
                return new GuzzleResponse(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    Utils::streamFor($body),
                );
            }

            $modified = false;

            foreach ($data['choices'] as &$choice) {
                $content = $choice['message']['content'] ?? null;

                if (is_array($content)) {
                    $choice['message']['parsed'] = $content;
                    $choice['message']['content'] = json_encode($content);
                    $modified = true;
                }
            }

            return new GuzzleResponse(
                $response->getStatusCode(),
                $response->getHeaders(),
                Utils::streamFor($modified ? json_encode($data) : $body),
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
