<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\FuncCall;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32HelperFunctionRectorTest extends AbstractRectorTestCase
{
    public function testHelperFunctionConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/helper_functions.php.inc');
    }

    public function testSkipNonHelperFunctions(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/skip_non_helper.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/helper_function_rule.php';
    }
}
