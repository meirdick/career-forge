<?php

namespace App\Console\Commands;

use App\Contracts\WebScraperContract;
use App\Services\Scrapers\AtsApiDriver;
use App\Services\Scrapers\CloudflareScraperDriver;
use App\Services\Scrapers\ContentQualityAnalyzer;
use App\Services\Scrapers\FirecrawlScraperDriver;
use App\Services\Scrapers\JsonLdScraperDriver;
use Illuminate\Console\Command;

class TestScrapersCommand extends Command
{
    protected $signature = 'scraper:test
        {--url= : Test a single URL}
        {--driver= : Test specific driver (ats|jsonld|cloudflare|firecrawl)}
        {--board= : Test a specific board by name}';

    protected $description = 'Test scraper drivers against real job board URLs and measure content quality';

    /** @var array<string, string> */
    private const array CURATED_URLS = [
        'greenhouse' => 'https://boards.greenhouse.io/figma/jobs/5232157004',
        'lever' => 'https://jobs.lever.co/cloudflare/1234abcd',
        'workday' => 'https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA---California-Mountain-View/Senior-Director--Lead-Product-Manager---Norton-360_55006',
        'ashby' => 'https://jobs.ashbyhq.com/notion/12345',
        'risepeople' => 'https://careers.risepeople.com/exa-software-inc/en/15507_director-of-ai-enablement',
        'smartrecruiters' => 'https://jobs.smartrecruiters.com/Bosch/12345-software-engineer',
        'icims' => 'https://careers-accenture.icims.com/jobs/12345/job',
        'bamboohr' => 'https://startupco.bamboohr.com/careers/12345',
        'jobvite' => 'https://jobs.jobvite.com/logitech/job/12345',
        'taleo' => 'https://oracle.taleo.net/careersection/2/jobdetail.ftl?job=12345',
        'jazz' => 'https://startupco.jazz.co/apply/12345',
        'breezy' => 'https://startupco.breezy.hr/p/12345-software-engineer',
        'recruitee' => 'https://company.recruitee.com/o/software-engineer',
        'applied' => 'https://app.beapplied.com/apply/12345',
        'rippling' => 'https://ats.rippling.com/company/jobs/12345',
        'indeed' => 'https://www.indeed.com/viewjob?jk=abc123',
    ];

    public function handle(): int
    {
        $urls = $this->resolveUrls();
        $drivers = $this->resolveDrivers();

        if ($urls === []) {
            $this->error('No URLs to test.');

            return self::FAILURE;
        }

        if ($drivers === []) {
            $this->error('No configured drivers available.');

            return self::FAILURE;
        }

        $results = [];

        foreach ($urls as $board => $url) {
            $this->info("\n Testing: {$board}");
            $this->line("  URL: {$url}");

            foreach ($drivers as $driverName => $driver) {
                $start = microtime(true);
                $content = $driver->scrape($url);
                $elapsed = round(microtime(true) - $start, 2);

                if ($content) {
                    $quality = ContentQualityAnalyzer::analyze($content);
                    $results[] = [
                        $board,
                        $driverName,
                        $quality->isValid ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                        "{$quality->score}/{$quality->maxScore}",
                        mb_strlen($content),
                        "{$elapsed}s",
                        $this->formatSignals($quality->signals),
                    ];
                } else {
                    $results[] = [
                        $board,
                        $driverName,
                        '<fg=yellow>NULL</>',
                        '-',
                        0,
                        "{$elapsed}s",
                        'No content returned',
                    ];
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Board', 'Driver', 'Result', 'Score', 'Length', 'Time', 'Signals'],
            $results,
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function resolveUrls(): array
    {
        if ($url = $this->option('url')) {
            $host = parse_url($url, PHP_URL_HOST) ?? 'custom';

            return [$host => $url];
        }

        if ($board = $this->option('board')) {
            $board = strtolower($board);

            if (isset(self::CURATED_URLS[$board])) {
                return [$board => self::CURATED_URLS[$board]];
            }

            $this->error("Unknown board: {$board}. Available: ".implode(', ', array_keys(self::CURATED_URLS)));

            return [];
        }

        return self::CURATED_URLS;
    }

    /**
     * @return array<string, WebScraperContract>
     */
    private function resolveDrivers(): array
    {
        $driverMap = [
            'ats' => new AtsApiDriver,
            'jsonld' => new JsonLdScraperDriver,
            'cloudflare' => new CloudflareScraperDriver,
            'firecrawl' => new FirecrawlScraperDriver,
        ];

        if ($driverOption = $this->option('driver')) {
            $driverOption = strtolower($driverOption);

            if (! isset($driverMap[$driverOption])) {
                $this->error("Unknown driver: {$driverOption}. Available: ats, jsonld, cloudflare, firecrawl");

                return [];
            }

            $driver = $driverMap[$driverOption];

            if (! $driver->isConfigured()) {
                $this->error("Driver {$driverOption} is not configured.");

                return [];
            }

            return [$driverOption => $driver];
        }

        return array_filter($driverMap, fn (WebScraperContract $driver) => $driver->isConfigured());
    }

    /**
     * @param  array<string, bool>  $signals
     */
    private function formatSignals(array $signals): string
    {
        $parts = [];

        foreach ($signals as $name => $passed) {
            $parts[] = ($passed ? '+' : '-').$name;
        }

        return implode(' ', $parts);
    }
}
