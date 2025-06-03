<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\Class_;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32ControllerToThinkPHP50RectorTest extends AbstractRectorTestCase
{
    public function testControllerConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/simple_test.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}