<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Tests\Rector\StaticCall;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ThinkPHP50To51RouteRectorTest extends AbstractRectorTestCase
{
    public function testRouteRuleToRules(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture_Route/route_rule_to_rules.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/route_rule.php';
    }
}
