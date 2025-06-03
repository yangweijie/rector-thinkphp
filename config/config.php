<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withSets([
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ]);
