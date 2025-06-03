<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\StaticCall\ThinkPHP50To51RouteRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP50To51RouteRector::class,
    ]);
