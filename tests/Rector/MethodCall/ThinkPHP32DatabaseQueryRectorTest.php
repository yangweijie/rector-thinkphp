<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\MethodCall;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP32DatabaseQueryRectorTest extends AbstractRectorTestCase
{
    public function testDatabaseQueryConversion(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_DatabaseQuery/basic_query.php.inc');
    }

    public function testSkipNonMQueries(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_DatabaseQuery/skip_non_m.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/database_query_rule.php';
    }
}
