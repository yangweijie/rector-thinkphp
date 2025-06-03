<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Use_\ThinkPHP50To51NamespaceRector;
use Rector\ThinkPHP\Rector\FuncCall\ThinkPHP50To51ConfigRector;
use Rector\ThinkPHP\Rector\ConstFetch\ThinkPHP50To51ConstantRector;
use Rector\ThinkPHP\Rector\ClassMethod\ThinkPHP50To51ControllerInitRector;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP50To51WhereArrayRector;
use Rector\ThinkPHP\Rector\StaticCall\ThinkPHP50To51RouteRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP50To51NamespaceRector::class,
        ThinkPHP50To51ConfigRector::class,
        ThinkPHP50To51ConstantRector::class,
        ThinkPHP50To51ControllerInitRector::class,
        ThinkPHP50To51WhereArrayRector::class,
        ThinkPHP50To51RouteRector::class,
    ]);
