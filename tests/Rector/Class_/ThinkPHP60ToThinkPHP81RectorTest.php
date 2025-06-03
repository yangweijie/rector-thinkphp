<?php

declare(strict_types=1);

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP60ToThinkPHP81RectorTest extends AbstractRectorTestCase
{
    public function testConstructorPromotion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp60_to_81/constructor_promotion.php.inc');
    }

    public function testReadonlyProperties(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp60_to_81/readonly_properties.php.inc');
    }

    public function testSkipNonThinkPHPClasses(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp60_to_81/skip_non_thinkphp.php.inc');
    }

    public function testComplexInheritance(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp60_to_81/complex_inheritance.php.inc');
    }

    public function testEdgeCases(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp60_to_81/edge_cases.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/thinkphp60_to_81_rule.php';
    }
}
