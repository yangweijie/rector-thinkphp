<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\Use_;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP50To51NamespaceRectorTest extends AbstractRectorTestCase
{
    public function testNamespaceConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_Namespace/namespace_conversion.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/namespace_rule.php';
    }
}
