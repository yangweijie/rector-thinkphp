<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\Stmt;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32EchoToReturnRectorTest extends AbstractRectorTestCase
{
    public function testEchoToReturn(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_EchoToReturn/basic_echo.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/echo_to_return_rule.php';
    }
}
