<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Extension;

use Rector\ThinkPHP\Command\UpgradeWizardCommand;
use Rector\ThinkPHP\Command\BatchUpgradeCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class ThinkPHPExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Register commands
        $container->register(UpgradeWizardCommand::class)
            ->addTag('console.command');
            
        $container->register(BatchUpgradeCommand::class)
            ->addTag('console.command');
    }
}
