<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

it('can load all ThinkPHP upgrade sets', function () {
    $sets = [
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_81,
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ];
    
    foreach ($sets as $set) {
        expect(file_exists($set))->toBeTrue("Set file should exist: {$set}");
        
        $config = require $set;
        expect($config)->toBeInstanceOf(RectorConfigBuilder::class);
    }
});

it('has proper upgrade path from 3.2 to 8.1', function () {
    // Test that we can create a complete upgrade configuration
    $config = RectorConfig::configure()
        ->withSets([
            ThinkPHPSetList::THINKPHP_32_TO_50,
            ThinkPHPSetList::THINKPHP_50_TO_60,
            ThinkPHPSetList::THINKPHP_60_TO_81,
        ]);
    
    expect($config)->toBeInstanceOf(RectorConfigBuilder::class);
});

it('validates ThinkPHP version constants', function () {
    $expectedVersions = ['3.1', '3.2', '5.0', '5.1', '6.0', '6.1', '8.0', '8.1'];
    
    expect(\Rector\ThinkPHP\ValueObject\ThinkPHPVersion::ALL_VERSIONS)
        ->toBe($expectedVersions)
        ->and(\Rector\ThinkPHP\ValueObject\ThinkPHPVersion::ALL_VERSIONS)
        ->not->toContain('4.0') // Ensure no 4.x versions
        ->not->toContain('4.1');
});