<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Stmt\ThinkPHP32DisplayToReturnFetchRector;
use Rector\ThinkPHP\Rector\MethodCall\FacadeCallToServiceRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32DisplayToReturnFetchRector::class,
        FacadeCallToServiceRector::class,
    ]);
