<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * ThinkPHP 5.0 to 6.0 direct upgrade configuration
 *
 * This combines the rules from 5.0->5.1 and 5.1->6.0 for direct upgrade
 */
return RectorConfig::configure()
    ->withSets([
        __DIR__ . '/thinkphp-50-to-51.php',
        __DIR__ . '/thinkphp-51-to-60.php',
    ]);
