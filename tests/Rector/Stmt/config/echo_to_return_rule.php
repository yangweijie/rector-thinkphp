<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Stmt\ThinkPHP32EchoToReturnRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32EchoToReturnRector::class,
    ]);
