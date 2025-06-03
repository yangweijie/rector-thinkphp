<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP50ToThinkPHP60Rector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP50ToThinkPHP60Rector::class,
    ]);
