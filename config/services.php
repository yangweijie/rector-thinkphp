<?php

declare(strict_types=1);

use Rector\ThinkPHP\Command\UpgradeWizardCommand;
use Rector\ThinkPHP\Command\BatchUpgradeCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // Register ThinkPHP upgrade commands
    $services->set(UpgradeWizardCommand::class)
        ->tag('console.command');

    $services->set(BatchUpgradeCommand::class)
        ->tag('console.command');
};
