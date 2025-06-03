<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\ConstFetch\ThinkPHP32SystemConstantRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32SystemConstantRector::class,
    ]);
