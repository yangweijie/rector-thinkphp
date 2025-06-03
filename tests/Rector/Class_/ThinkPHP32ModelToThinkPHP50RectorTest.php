<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\Class_;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32ModelToThinkPHP50RectorTest extends AbstractRectorTestCase
{
    public function testModelConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/model_conversion.php.inc');
    }

    public function testModelWithTableName(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/model_with_table_name.php.inc');
    }

    public function testSkipNonModelClasses(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/skip_non_model.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_model_rule.php';
    }
}