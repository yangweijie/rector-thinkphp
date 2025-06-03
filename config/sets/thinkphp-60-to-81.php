<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP60ToThinkPHP81Rector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP60ToThinkPHP81Rector::class,
    ]);
