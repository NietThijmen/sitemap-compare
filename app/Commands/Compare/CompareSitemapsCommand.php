<?php

namespace App\Commands\Compare;

use App\Service\SitemapSearcher;
use Illuminate\Console\Command;

class CompareSitemapsCommand extends Command
{
    protected $signature = 'compare:sitemaps {source1 : The first sitemap source URL} {source2 : The second sitemap source URL} {--output-format=cli : Output format (cli, json,null)}';

    protected $description = 'Compare sitemaps from two different sources';

    private function normaliseUrls(
        array $urls
    ) {
        // remove trailing slashes + replace source1 and source2 with WEBSITE_URL
        $urls = array_map(function ($url) {
            return rtrim($url, '/');
        }, $urls);

        $urls = array_map(function ($url) {
            return str_ireplace(
                $this->argument('source1'),
                'WEBSITE_URL',
                $url
            );
        }, $urls);

        $urls = array_map(function ($url) {
            return str_ireplace(
                $this->argument('source2'),
                'WEBSITE_URL',
                $url
            );
        }, $urls);

        return $urls;
    }

    public function handle(): void
    {
        $sitemap_one =
            SitemapSearcher::findSitemaps(
                $this->argument('source1')
            );

        $sitemap_two =
            SitemapSearcher::findSitemaps(
                $this->argument('source2')
            );

        $this->info('Retrieved sitemaps from both sources.');

        $urls_one = SitemapSearcher::getUrlsFromSitemaps($sitemap_one);
        $urls_two = SitemapSearcher::getUrlsFromSitemaps($sitemap_two);

        $urls_one = $this->normaliseUrls($urls_one);
        $urls_two = $this->normaliseUrls($urls_two);

        $this->info('Retrieved URLs from both sitemap sources.');

        $only_in_one = array_diff($urls_one, $urls_two);
        $only_in_two = array_diff($urls_two, $urls_one);

        $this->info('Diffrence calculated.');
        $this->line('Only in source 1: '.count($only_in_one).' URLs');
        $this->line('Only in source 2: '.count($only_in_two).' URLs');

        $this->output->newLine(10);

        switch ($this->option('output-format')) {
            case 'json':
                $this->outputJson($only_in_one, $only_in_two);
                break;
            case 'cli':
            default:
                $this->outputCli($only_in_one, $only_in_two);
                break;
        }

    }

    private function outputCli(
        array $only_in_one,
        array $only_in_two
    ) {
        $this->info('URLs only in source 1:');
        foreach ($only_in_one as $url) {
            $this->line($url);
        }

        $this->info('URLs only in source 2:');
        foreach ($only_in_two as $url) {
            $this->line($url);
        }
    }

    private function outputJson(array $only_in_one, array $only_in_two)
    {
        $result = [
            'only_in_source_1' => array_values($only_in_one),
            'only_in_source_2' => array_values($only_in_two),
        ];
        $this->line(json_encode($result, JSON_PRETTY_PRINT));
    }
}
