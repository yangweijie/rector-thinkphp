<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Command\UpgradeWizardCommand;
use Rector\ThinkPHP\Command\BatchUpgradeCommand;

return static function (RectorConfig $rectorConfig): void {
    // Register services
    $services = $rectorConfig->services();
    
    $services->set(UpgradeWizardCommand::class)
        ->tag('console.command');
        
    $services->set(BatchUpgradeCommand::class)
        ->tag('console.command');
};
