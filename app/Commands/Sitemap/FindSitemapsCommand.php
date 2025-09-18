<?php

namespace App\Commands\Sitemap;

use Illuminate\Console\Command;

/**
 * Command to find and list all sitemaps from a given URL
 */
class FindSitemapsCommand extends Command
{
    protected $signature = 'sitemap:find {url : The URL to search for sitemaps}';

    protected $description = 'Find and list all sitemaps from a given URL';

    public function handle(): void
    {
        $url = $this->argument('url');

        $this->info("Searching for sitemaps at: $url");

        $sitemaps = \App\Service\SitemapSearcher::findSitemaps($url);

        if (empty($sitemaps)) {
            $this->warn('No sitemaps found.');
        } else {
            $this->info('Found sitemaps:');
            foreach ($sitemaps as $sitemap) {
                $this->line($sitemap);
            }
        }

        $this->info('Search completed.');
    }
}
