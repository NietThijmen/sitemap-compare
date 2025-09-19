<?php

$composer_json = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
$version = $composer_json['version'] ?? '1.0.0';

echo "Build version: $version\n";

exec(
    "php sitemap-compare app:build --build-version=$version --no-interaction",
);

echo "Build completed.\n";
