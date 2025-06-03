<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ControllerToThinkPHP50Rector;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ModelToThinkPHP50Rector;
use Rector\ThinkPHP\Rector\Stmt\ThinkPHP32DisplayToReturnFetchRector;
use Rector\ThinkPHP\Rector\FuncCall\ThinkPHP32HelperFunctionRector;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP32DatabaseQueryRector;
use Rector\ThinkPHP\Rector\Stmt\ThinkPHP32EchoToReturnRector;
use Rector\ThinkPHP\Rector\ConstFetch\ThinkPHP32SystemConstantRector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32ControllerToThinkPHP50Rector::class,
        ThinkPHP32ModelToThinkPHP50Rector::class,
        ThinkPHP32DisplayToReturnFetchRector::class,
        ThinkPHP32HelperFunctionRector::class,
        ThinkPHP32DatabaseQueryRector::class,
        ThinkPHP32EchoToReturnRector::class,
        ThinkPHP32SystemConstantRector::class,
    ])
    ->withConfiguredRule(ThinkPHP32ControllerToThinkPHP50Rector::class, [
        'namespace' => 'app\\index\\controller',
    ])
    ->withConfiguredRule(ThinkPHP32ModelToThinkPHP50Rector::class, [
        'model_namespace' => 'app\\index\\model',
    ]);
