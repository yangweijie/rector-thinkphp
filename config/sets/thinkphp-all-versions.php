<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withSets([
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_51,
        ThinkPHPSetList::THINKPHP_51_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_80,
    ]);
