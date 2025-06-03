<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\FuncCall\ThinkPHP32HelperFunctionRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32HelperFunctionRector::class,
    ]);
