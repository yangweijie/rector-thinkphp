<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\StaticCall\ThinkPHP51To60ConfigRector;
use Rector\ThinkPHP\Rector\Use_\ThinkPHP51To60DbFacadeRector;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP51To60SetIncDecRector;
use Rector\ThinkPHP\Rector\StaticCall\ThinkPHP51To60ModelMethodRector;
use Rector\ThinkPHP\Rector\Use_\ThinkPHP51To60FacadeAliasRector;
use Rector\ThinkPHP\Rector\Use_\ThinkPHP51To60ControllerRector;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP51To60InsertReplaceRector;
use Rector\ThinkPHP\Rector\FuncCall\ThinkPHP51To60HelperFunctionRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP51To60ConfigRector::class,
        ThinkPHP51To60DbFacadeRector::class,
        ThinkPHP51To60SetIncDecRector::class,
        ThinkPHP51To60ModelMethodRector::class,
        ThinkPHP51To60FacadeAliasRector::class,
        ThinkPHP51To60ControllerRector::class,
        ThinkPHP51To60InsertReplaceRector::class,
        ThinkPHP51To60HelperFunctionRector::class,
    ]);
