<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ModelToThinkPHP50Rector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32ModelToThinkPHP50Rector::class,
    ]);
