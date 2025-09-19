<?php

namespace App\Commands\Compare;

use App\Service\RetryService;
use Illuminate\Console\Command;
use Spatie\Fork\Fork;

class CompareSitemapAToWebsiteB extends Command
{
    protected $signature = 'compare:websites {source1 : The website where the sitemaps will be used from} {source2 : The url to check towards} {--output-format=cli : Output format (cli, json, null)} {--concurrency=10 : Number of concurrent requests}';

    protected $description = '
        Compare the URLs found in the sitemaps of website A (source1) to the actual URLs found on website B (source2).
        This helps to identify discrepancies between the intended structure of website A and the actual structure of website
    ';

    protected function checkUrl(
        string $url
    ): array {
        $started_at = microtime(true);

        $response = \Illuminate\Support\Facades\Http::get($url);

        $ended_at = microtime(true);
        $duration = $ended_at - $started_at;

        if ($response->successful()) {
            $this->info("Found: $url (Status: {$response->status()}, Time: ".number_format($duration, 2).'s)');
        } else {
            $this->warn("Not Found (Status: {$response->status()}): $url");
        }

        return [
            'is_successful' => $response->successful(),
            'status' => $response->status(),
            'duration' => $duration,
            'url' => $url,
        ];
    }

    public function handle(): void
    {

        $source1 = $this->argument('source1');
        $source2 = $this->argument('source2');

        $confirmation = $this->ask("
            Warning: This command will make multiple HTTP requests to $source2 based on the sitemaps found in $source1.
            Ensure you have permission to perform this action to avoid potential issues. Do you want to proceed? (yes/no)
        ");

        if (strtolower($confirmation) !== 'yes') {
            $this->info('Operation cancelled by user.');

            return;
        }

        $sitemaps = \App\Service\SitemapSearcher::findSitemaps($source1);
        $sitemapUrls = \App\Service\SitemapSearcher::getUrlsFromSitemaps($sitemaps);
        $this->info("Retrieved sitemaps from source1: $source1");

        $sitemapUrls = array_map(function ($url) {
            return rtrim($url, '/');
        }, $sitemapUrls);

        $sitemapUrls = array_map(function ($url) use ($source1, $source2) {
            return str_ireplace($source1, $source2, $url);
        }, $sitemapUrls);

        $this->info("Checking URLs against source2: $source2");
        $foundUrls = [];

        $forkArray = [];
        foreach ($sitemapUrls as $url) {
            $forkArray[] = function () use ($url) {
                /**
                 * Retry mechanism to handle errors gracefully instead of just killing the whole process
                 * if one URL fails.
                 */
                return RetryService::Retry(
                    3,
                    fn () => $this->checkUrl($url),
                    500,
                    fn ($exception) => $this->warn("[Retry] Error checking URL $url: ".$exception->getMessage()),
                    fn ($exception) => $this->warn("[Retry] Failed to check URL $url after multiple attempts: ".$exception->getMessage())
                );
            };
        }

        $concurrency = (int) $this->option('concurrency');
        $outputs = Fork::new()
            ->concurrent($concurrency)
            ->run(...$forkArray);

        foreach ($outputs as $output) {
            try {
                if ($output['is_successful']) {
                    $foundUrls[] = $output['url'];
                }
                // @phpstan-ignore-next-line
            } catch (\Throwable $exception) {
                $this->warn('Unknown output from output array: '.json_encode($output));
            }
        }

        $this->info('Comparison completed. Found '.count($foundUrls).' out of '.count($sitemapUrls).' URLs.');

        $this->output->newLine(10);
        $outputFormat = $this->option('output-format');
        switch ($outputFormat) {
            case 'json':
                $this->outputJson($sitemapUrls, $foundUrls);
                break;
            case 'cli':
            default:
                $this->outputCli($sitemapUrls, $foundUrls);
                break;
        }
    }

    private function outputCli(
        array $sitemapUrls,
        array $foundUrls
    ): void {
        $this->info('Sitemap URLs from source1:');
        foreach ($sitemapUrls as $url) {
            $this->line($url);
        }

        $this->info("\nFound URLs on source2:");
        foreach ($foundUrls as $url) {
            $this->line($url);
        }

        $this->info("\nUrls not found on source2:");
        $notFoundUrls = array_diff($sitemapUrls, $foundUrls);
        foreach ($notFoundUrls as $url) {
            $this->line($url);
        }

        $this->info("\nSummary:");
        $this->line('Total URLs in sitemap: '.count($sitemapUrls));
        $this->line('Total URLs found on source2: '.count($foundUrls));
        $this->line('Total URLs not found on source2: '.count($notFoundUrls));
    }

    private function outputJson(
        array $sitemapUrls,
        array $foundUrls
    ): void {

        $urlsNotFound = array_values(array_diff($sitemapUrls, $foundUrls));
        $output = [
            'sitemap_urls' => $sitemapUrls,
            'found_urls' => $foundUrls,
            'urls_not_found' => $urlsNotFound,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }
}
