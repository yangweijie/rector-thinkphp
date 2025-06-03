<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * ThinkPHP 6.* to 8.0 upgrade configuration
 * 
 * According to the official documentation, 8.0 version supports seamless upgrade from 6.* versions.
 * If upgrading from 6.0, you need to separately install the think-filesystem library.
 * 
 * This configuration file is mainly for completeness and future extensions.
 */
return RectorConfig::configure()
    ->withRules([
        // Currently no specific rules needed for 6.* to 8.0 upgrade
        // The upgrade is mostly seamless according to documentation
    ]);
