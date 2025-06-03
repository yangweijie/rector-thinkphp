<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ControllerToThinkPHP50Rector;
use Rector\ThinkPHP\Rector\Stmt\ThinkPHP32DisplayToReturnFetchRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32ControllerToThinkPHP50Rector::class,
        ThinkPHP32DisplayToReturnFetchRector::class,
    ]);
