<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP32DatabaseQueryRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32DatabaseQueryRector::class,
    ]);
