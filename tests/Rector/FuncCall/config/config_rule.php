<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\FuncCall\ThinkPHP50To51ConfigRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP50To51ConfigRector::class,
    ]);
