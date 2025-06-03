<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\ConstFetch;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32SystemConstantRectorTest extends AbstractRectorTestCase
{
    public function testSystemConstants(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_SystemConstants/basic_constants.php.inc');
    }

    public function testSkipNonSystemConstants(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_SystemConstants/skip_non_system.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/system_constant_rule.php';
    }
}
