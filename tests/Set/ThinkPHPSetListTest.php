<?php

declare(strict_types=1);

use Rector\ThinkPHP\Set\ThinkPHPSetList;

it('has all required set constants', function () {
    expect(ThinkPHPSetList::THINKPHP_32_TO_50)->toBeString();
    expect(ThinkPHPSetList::THINKPHP_50_TO_60)->toBeString();
    expect(ThinkPHPSetList::THINKPHP_60_TO_81)->toBeString();
    expect(ThinkPHPSetList::THINKPHP_ALL_VERSIONS)->toBeString();
});

it('has valid file paths for all sets', function () {
    $sets = [
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_81,
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ];

    foreach ($sets as $set) {
        expect(file_exists($set))->toBeTrue("Set file should exist: {$set}");
    }
});

it('can load all set configurations', function () {
    $sets = [
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_81,
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ];

    foreach ($sets as $set) {
        $config = require $set;
        expect($config)->toBeInstanceOf(\Rector\Configuration\RectorConfigBuilder::class);
    }
});
