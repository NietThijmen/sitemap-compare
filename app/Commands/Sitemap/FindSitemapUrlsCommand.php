<?php

namespace App\Commands\Sitemap;

use Illuminate\Console\Command;

class FindSitemapUrlsCommand extends Command
{
    protected $signature = 'sitemap:urls {url : The URL to search for sitemap URLs}';

    protected $description = 'Find all urls from the sitemaps of a given URL';

    public function handle(): void
    {
        $url = $this->argument('url');

        $this->info("Searching for sitemap URLs at: $url");

        $sitemaps = \App\Service\SitemapSearcher::findSitemaps($url);
        $urls = \App\Service\SitemapSearcher::getUrlsFromSitemaps($sitemaps);

        if (empty($urls)) {
            $this->warn('No URLs found in sitemaps.');
        } else {
            $this->info('Found URLs:');
            foreach ($urls as $sitemapUrl) {
                $this->line($sitemapUrl);
            }
        }

        $this->info('Search completed.');

    }
}
