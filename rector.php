<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ])
    ->withSkip([
        __DIR__ . '/tests/Rector/*/Fixture/*',
    ]);
