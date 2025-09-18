<?php

namespace App\Service;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Class SitemapSearcher
 *
 * @author  Thijmen Rierink <thijmen@rierink.dev>
 * @license proprietary
 *
 * @link    https://rierink.dev
 */
class SitemapSearcher
{
    /**
     * @param  string  $url  the base URL of the website
     * @param  string|null  $path  optional path to a specific sitemap index
     * @return array of sitemap URLs
     *
     * @throws ConnectionException
     */
    private static function getSitemapsFromIndexXml(
        string $url,
        ?string $path = null
    ): array {

        $sitemaps = [];

        $pathsToSearch = [
            '/sitemap_index.xml',
            '/sitemap.xml',
        ];

        if ($path) {
            $pathsToSearch[] = $path;
        }

        foreach ($pathsToSearch as $pathToSearch) {
            $fullUrl = rtrim($url, '/').$pathToSearch;
            $rawResponse = Http::get($fullUrl);

            if ($rawResponse->status() !== 200) {
                continue;
            }

            $rawBody = $rawResponse->body();

            $xml = simplexml_load_string($rawBody);
            if ($xml === false) {
                continue;
            }

            // xml to array
            $json = json_encode($xml);
            $array = json_decode($json, true);

            if (! isset($array['sitemap'])) {
                continue;
            }

            $array = $array['sitemap'];

            foreach ($array as $item) {
                if (isset($item['loc'])) {
                    $sitemaps[] = $item['loc'];
                }
            }
        }

        return array_unique($sitemaps);
    }

    /**
     * Try to get sitemap URL from robots.txt
     *
     * @param  string  $url  the base URL of the website
     *
     * @throws ConnectionException
     */
    private static function getSitemapFromRobotsTxt(
        string $url
    ): ?string {
        $robotsUrl = rtrim($url, '/').'/robots.txt';
        $rawResponse = Http::get($robotsUrl);

        if ($rawResponse->status() !== 200) {
            return null;
        }

        $rawBody = $rawResponse->body();
        $lines = explode("\n", $rawBody);

        foreach ($lines as $line) {
            if (stripos($line, 'Sitemap:') === 0) {
                $sitemapUrl = trim(substr($line, 8));
                if (filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
                    return $sitemapUrl;
                }
            }
        }

        return null;
    }

    /**
     * @param  string  $url  the base URL of the website
     * @return string[]
     */
    public static function findSitemaps(
        string $url
    ): array {
        if (! str_starts_with($url, 'http') || ! str_starts_with($url, 'https')) {
            $url = 'https://'.$url;
        }

        $sitemaps = [];
        $sitemapFromRobots = self::getSitemapFromRobotsTxt($url);
        if ($sitemapFromRobots) {
            $sitemaps[] = $sitemapFromRobots;
            $sitemaps = array_merge($sitemaps, self::getSitemapsFromIndexXml($url, parse_url($sitemapFromRobots, PHP_URL_PATH)));
        }
        $sitemaps = array_merge($sitemaps, self::getSitemapsFromIndexXml($url));

        return array_unique($sitemaps);
    }

    /**
     * Get all URLs from an array of sitemap URLs
     *
     * @param  string[]  $sitemaps
     * @return string[]
     *
     * @throws ConnectionException
     */
    public static function getUrlsFromSitemaps(
        array $sitemaps
    ): array {
        $urls = [];
        foreach ($sitemaps as $sitemap) {
            $rawResponse = Http::get($sitemap);
            if ($rawResponse->status() !== 200) {
                continue;
            }

            $rawBody = $rawResponse->body();
            $xml = simplexml_load_string($rawBody);
            if ($xml === false) {
                continue;
            }

            // xml to array
            $json = json_encode($xml);
            $array = json_decode($json, true);

            if (! isset($array['url'])) {
                continue;
            }

            $array = $array['url'];

            foreach ($array as $item) {
                if (isset($item['loc'])) {
                    $urls[] = $item['loc'];
                }
            }
        }

        return array_unique($urls);

    }
}
