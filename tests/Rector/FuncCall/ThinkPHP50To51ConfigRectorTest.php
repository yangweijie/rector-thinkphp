<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\FuncCall;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP50To51ConfigRectorTest extends AbstractRectorTestCase
{
    public function testConfigConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_Config/config_conversion.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/config_rule.php';
    }
}
