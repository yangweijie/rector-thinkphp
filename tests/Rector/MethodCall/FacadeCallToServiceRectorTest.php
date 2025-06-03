<?php

declare(strict_types=1);

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class FacadeCallToServiceRectorTest extends AbstractRectorTestCase
{
    public function testBasicDbFacade(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/facade_to_service/basic_db_facade.php.inc');
    }

    public function testMultipleFacades(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/facade_to_service/multiple_facades.php.inc');
    }

    public function testExistingConstructor(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/facade_to_service/existing_constructor.php.inc');
    }

    public function testSkipNonFacade(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/facade_to_service/skip_non_facade.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/facade_to_service_rule.php';
    }
}
