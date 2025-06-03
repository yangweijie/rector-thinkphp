<?php

declare(strict_types=1);

use Rector\ThinkPHP\ValueObject\ThinkPHPVersion;

it('creates version object with valid version', function () {
    $version = new ThinkPHPVersion(ThinkPHPVersion::VERSION_3_1);
    expect($version->getVersion())->toBe('3.1');
});

it('throws exception for invalid version', function () {
    expect(fn() => new ThinkPHPVersion('4.0'))
        ->toThrow(InvalidArgumentException::class, 'Invalid ThinkPHP version "4.0"');
});

it('compares versions correctly', function () {
    $version31 = new ThinkPHPVersion(ThinkPHPVersion::VERSION_3_1);
    $version50 = new ThinkPHPVersion(ThinkPHPVersion::VERSION_5_0);
    $version81 = new ThinkPHPVersion(ThinkPHPVersion::VERSION_8_1);
    
    expect($version31->isLessThan($version50))->toBeTrue();
    expect($version50->isGreaterThan($version31))->toBeTrue();
    expect($version81->isGreaterThan($version50))->toBeTrue();
    expect($version31->equals($version31))->toBeTrue();
});

it('has all valid versions in ALL_VERSIONS constant', function () {
    $expectedVersions = ['3.1', '3.2', '5.0', '5.1', '6.0', '6.1', '8.0', '8.1'];
    expect(ThinkPHPVersion::ALL_VERSIONS)->toBe($expectedVersions);
});

it('converts to string correctly', function () {
    $version = new ThinkPHPVersion(ThinkPHPVersion::VERSION_5_0);
    expect((string) $version)->toBe('5.0');
});
