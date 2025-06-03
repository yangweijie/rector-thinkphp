<?php

declare(strict_types=1);

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP50ToThinkPHP60RectorTest extends AbstractRectorTestCase
{
    public function testBasicControllerConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/basic_controller.php.inc');
    }

    public function testNamespaceUpdate(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/namespace_update.php.inc');
    }

    public function testReturnTypeHints(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/return_type_hints.php.inc');
    }

    public function testModelConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/model_conversion.php.inc');
    }

    public function testSkipNonThinkPHPClasses(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/skip_non_thinkphp.php.inc');
    }

    public function testEdgeCases(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/thinkphp50_to_60/edge_cases.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/thinkphp50_to_60_rule.php';
    }
}
