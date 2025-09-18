# Sitemap finder
Find sitemaps, urls, compare sitemaps and even compare websites to sitemaps.

## Features
- Find sitemaps from a website
- Find urls from a sitemap
- Compare two sitemaps
- Compare a website to a sitemap
- Export results to JSON or CLI format
- Multithreaded for speed (requires pcntl extension)
- Command line interface (CLI) for easy use

## Requirements
- PHP 8.0 or higher
- Composer
- pcntl extension
- cURL extension
- SimpleXML extension
- JSON extension
- mbstring extension


## Installation
1. Clone the repository:
```
    git clone <TODO: REPO URL>
```

2. Navigate to the project directory:
```
    cd sitemap-finder
```

3. Install dependencies using Composer:
```
    composer install
```

4. Run the application:
```
    php sitemap-compare list
```
   

## Usage
### Find sitemaps from a website
```
    php sitemap-compare sitemap:find <website_url>
```

### Find urls from a sitemap
```
    php sitemap-compare url:find <website_url>
```

### Compare two sitemaps
```
    php sitemap-compare compare:sitemaps <url 1> <url 2> --output-format=json
```

### Compare a website to a sitemap
Warning, this will take a long long long time<br/>
+ it will most likely stress the target website, so do it on your OWN servers<br/>
```
    php sitemap-compare compare:website <source url> <target url> --output-format=cli --concurrency=5
```
