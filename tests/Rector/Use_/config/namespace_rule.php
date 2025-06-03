<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Use_\ThinkPHP50To51NamespaceRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP50To51NamespaceRector::class,
    ]);
