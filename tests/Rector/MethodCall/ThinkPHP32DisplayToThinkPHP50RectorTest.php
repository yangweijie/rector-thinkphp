<?php

declare(strict_types=1);

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ThinkPHP32DisplayToThinkPHP50RectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_method_rule.php';
    }

    public function testSkipNonDisplayCalls(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/skip_non_display.php.inc');
    }
}