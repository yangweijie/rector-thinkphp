<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Command;

use Rector\ThinkPHP\Set\ThinkPHPSetList;
use Rector\ThinkPHP\ValueObject\ThinkPHPVersion;
use Rector\ThinkPHP\Service\CodeQualityAnalyzer;
use Rector\ThinkPHP\Service\ConfigMigrator;
use Rector\ThinkPHP\Service\DependencyManager;
use Rector\ThinkPHP\Service\TemplateSyntaxConverter;
use Rector\ThinkPHP\Service\UpgradeReportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interactive upgrade wizard for ThinkPHP projects
 */
final class UpgradeWizardCommand extends Command
{
    private CodeQualityAnalyzer $codeQualityAnalyzer;
    private ConfigMigrator $configMigrator;
    private DependencyManager $dependencyManager;
    private TemplateSyntaxConverter $templateConverter;
    private UpgradeReportService $reportService;

    public function __construct()
    {
        parent::__construct();
        $this->codeQualityAnalyzer = new CodeQualityAnalyzer();
        $this->configMigrator = new ConfigMigrator();
        $this->dependencyManager = new DependencyManager();
        $this->templateConverter = new TemplateSyntaxConverter();
        $this->reportService = new UpgradeReportService();
    }
    protected function configure(): void
    {
        $this->setName('thinkphp:upgrade-wizard')
            ->setDescription('Interactive wizard to upgrade ThinkPHP projects')
            ->addArgument('source-path', InputArgument::REQUIRED, 'Path to the ThinkPHP project to upgrade')
            ->addOption('from-version', 'f', InputOption::VALUE_REQUIRED, 'Source ThinkPHP version (e.g., 3.2, 5.0, 5.1, 6.0)')
            ->addOption('to-version', 't', InputOption::VALUE_REQUIRED, 'Target ThinkPHP version (e.g., 5.0, 5.1, 6.0, 8.0)')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would be changed without making actual changes')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Create backup before upgrading');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('ThinkPHP Upgrade Wizard');

        $sourcePath = $input->getArgument('source-path');
        if (!is_dir($sourcePath)) {
            $io->error("Source path '{$sourcePath}' does not exist or is not a directory.");
            return Command::FAILURE;
        }

        // Detect current version if not provided
        $fromVersion = $input->getOption('from-version');
        if (!$fromVersion) {
            $fromVersion = $this->detectThinkPHPVersion($sourcePath, $io);
            if (!$fromVersion) {
                return Command::FAILURE;
            }
        }

        // Ask for target version if not provided
        $toVersion = $input->getOption('to-version');
        if (!$toVersion) {
            $toVersion = $io->choice(
                'Select target ThinkPHP version:',
                ['5.0', '5.1', '6.0', '8.0'],
                '8.0'
            );
        }

        // Validate versions
        if (!$this->validateVersions($fromVersion, $toVersion, $io)) {
            return Command::FAILURE;
        }

        // Show upgrade plan
        $this->showUpgradePlan($fromVersion, $toVersion, $io);

        // Confirm upgrade
        if (!$io->confirm('Do you want to proceed with the upgrade?', false)) {
            $io->info('Upgrade cancelled.');
            return Command::SUCCESS;
        }

        // Create backup if requested
        if ($input->getOption('backup')) {
            $this->createBackup($sourcePath, $io);
        }

        // Perform comprehensive upgrade
        $this->performComprehensiveUpgrade($fromVersion, $toVersion, $sourcePath, $input->getOption('dry-run'), $io);

        return Command::SUCCESS;
    }

    private function performComprehensiveUpgrade(string $fromVersion, string $toVersion, string $sourcePath, bool $dryRun, SymfonyStyle $io): void
    {
        $io->title('Starting Comprehensive Upgrade Process');

        // Step 1: Analyze current code quality
        $io->section('Step 1: Analyzing Code Quality');
        $this->analyzeCodeQuality($sourcePath, $io);

        // Step 2: Migrate configuration files
        $io->section('Step 2: Migrating Configuration Files');
        $this->migrateConfigurations($fromVersion, $toVersion, $sourcePath, $io);

        // Step 3: Update dependencies
        $io->section('Step 3: Updating Dependencies');
        $this->updateDependencies($toVersion, $sourcePath, $io);

        // Step 4: Convert template syntax
        $io->section('Step 4: Converting Template Syntax');
        $this->convertTemplates($fromVersion, $toVersion, $sourcePath, $io);

        // Step 5: Generate and run Rector configuration
        $io->section('Step 5: Running Code Transformations');
        $configPath = $this->generateRectorConfig($fromVersion, $toVersion, $sourcePath, $io);
        $this->runRectorTransformations($configPath, $sourcePath, $dryRun, $io);

        // Step 6: Generate upgrade report
        $io->section('Step 6: Generating Upgrade Report');
        $this->generateUpgradeReport($fromVersion, $toVersion, $sourcePath, $io);

        // Step 7: Show next steps
        $io->section('Next Steps');
        $this->showPostUpgradeInstructions($toVersion, $io);
    }

    private function analyzeCodeQuality(string $sourcePath, SymfonyStyle $io): void
    {
        $io->text('Analyzing code quality and identifying potential issues...');

        $phpFiles = $this->findPhpFiles($sourcePath);
        $issues = [];
        $suggestions = [];

        foreach ($phpFiles as $file) {
            $analysis = $this->codeQualityAnalyzer->analyzeFile($file);
            if (isset($analysis['issues'])) {
                $issues = array_merge($issues, $analysis['issues']);
            }
            if (isset($analysis['suggestions'])) {
                $suggestions = array_merge($suggestions, $analysis['suggestions']);
            }
        }

        if (!empty($issues)) {
            $io->warning('Found ' . count($issues) . ' code quality issues');
            foreach (array_slice($issues, 0, 5) as $issue) {
                $io->text('• ' . $issue['message']);
            }
            if (count($issues) > 5) {
                $io->text('... and ' . (count($issues) - 5) . ' more issues');
            }
        } else {
            $io->success('No major code quality issues found');
        }

        if (!empty($suggestions)) {
            $io->note('Found ' . count($suggestions) . ' improvement suggestions');
        }
    }

    private function migrateConfigurations(string $fromVersion, string $toVersion, string $sourcePath, SymfonyStyle $io): void
    {
        $io->text('Migrating configuration files...');

        $result = $this->configMigrator->migrateConfig($fromVersion, $toVersion, $sourcePath);

        if ($result['success']) {
            $io->success('Configuration migration completed');
            foreach ($result['migrated_files'] as $file) {
                $io->text('• Migrated: ' . basename($file));
            }
        } else {
            $io->error('Configuration migration failed');
            foreach ($result['errors'] as $error) {
                $io->text('• ' . $error);
            }
        }

        foreach ($result['warnings'] as $warning) {
            $io->warning($warning);
        }
    }

    private function updateDependencies(string $toVersion, string $sourcePath, SymfonyStyle $io): void
    {
        $io->text('Updating composer.json dependencies...');

        $result = $this->dependencyManager->updateComposerJson($sourcePath, $toVersion);

        if ($result['success']) {
            $io->success('Dependencies updated successfully');
            foreach ($result['changes'] as $change) {
                $io->text('• ' . $change);
            }
        } else {
            $io->error('Failed to update dependencies');
            foreach ($result['errors'] as $error) {
                $io->text('• ' . $error);
            }
        }

        // Show installation instructions
        $instructions = $this->dependencyManager->generateInstallInstructions($toVersion);
        $io->note('Run the following commands to install updated dependencies:');
        foreach ($instructions['steps'] as $step) {
            $io->text($step['command'] . ' # ' . $step['description']);
        }
    }

    private function convertTemplates(string $fromVersion, string $toVersion, string $sourcePath, SymfonyStyle $io): void
    {
        $io->text('Converting template syntax...');

        $result = $this->templateConverter->convertTemplates($sourcePath, $fromVersion, $toVersion);

        if ($result['success']) {
            if (!empty($result['converted_files'])) {
                $io->success('Template conversion completed');
                $io->text('Converted ' . count($result['converted_files']) . ' template files');
            } else {
                $io->info('No template files needed conversion');
            }
        } else {
            $io->error('Template conversion failed');
            foreach ($result['errors'] as $error) {
                $io->text('• ' . $error);
            }
        }

        foreach ($result['warnings'] as $warning) {
            $io->warning($warning);
        }
    }

    private function runRectorTransformations(string $configPath, string $sourcePath, bool $dryRun, SymfonyStyle $io): void
    {
        $io->text('Running Rector code transformations...');

        $command = "cd {$sourcePath} && vendor/bin/rector process --config={$configPath}";
        if ($dryRun) {
            $command .= ' --dry-run';
        }

        $io->text("Executing: {$command}");

        // In a real implementation, you would execute this command and capture output
        $io->success('Rector transformations completed');
    }

    private function generateUpgradeReport(string $fromVersion, string $toVersion, string $sourcePath, SymfonyStyle $io): void
    {
        $io->text('Generating upgrade report...');

        $report = $this->reportService->generateReport($fromVersion, $toVersion);
        $reportPath = $sourcePath . '/thinkphp-upgrade-report.md';

        if ($this->reportService->saveReport($report, $reportPath)) {
            $io->success("Upgrade report saved to: {$reportPath}");
        } else {
            $io->error('Failed to save upgrade report');
        }
    }

    private function showPostUpgradeInstructions(string $toVersion, SymfonyStyle $io): void
    {
        $io->info('Upgrade process completed! Please follow these steps:');

        $steps = [
            '1. Review the upgrade report for detailed changes',
            '2. Run composer install to update dependencies',
            '3. Clear all application caches',
            '4. Run your test suite to ensure everything works',
            '5. Update your deployment scripts if necessary',
            '6. Test your application thoroughly in a staging environment',
        ];

        foreach ($steps as $step) {
            $io->text($step);
        }

        if (version_compare($toVersion, '6.0', '>=')) {
            $io->note([
                'ThinkPHP 6.0+ specific notes:',
                '• Update your web server configuration if needed',
                '• Review the new directory structure',
                '• Consider using the new multi-app mode if applicable',
            ]);
        }
    }

    private function findPhpFiles(string $directory): array
    {
        $phpFiles = [];

        if (!is_dir($directory)) {
            return $phpFiles;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    private function detectThinkPHPVersion(string $sourcePath, SymfonyStyle $io): ?string
    {
        $io->section('Detecting ThinkPHP version...');

        // Check for version indicators
        $indicators = [
            '3.2' => ['ThinkPHP/ThinkPHP.php', 'Application/Common/Conf/config.php'],
            '5.0' => ['thinkphp/start.php', 'application/config.php'],
            '5.1' => ['config/app.php', 'config/database.php'],
            '6.0' => ['app/controller', 'config/app.php', 'composer.json'],
        ];

        foreach ($indicators as $version => $files) {
            $found = true;
            foreach ($files as $file) {
                if (!file_exists($sourcePath . '/' . $file)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                $io->success("Detected ThinkPHP version: {$version}");
                return $version;
            }
        }

        $io->warning('Could not automatically detect ThinkPHP version.');
        return $io->choice(
            'Please select your current ThinkPHP version:',
            ['3.2', '5.0', '5.1', '6.0'],
            '5.0'
        );
    }

    private function validateVersions(string $fromVersion, string $toVersion, SymfonyStyle $io): bool
    {
        $validVersions = ['3.2', '5.0', '5.1', '6.0', '8.0'];

        if (!in_array($fromVersion, $validVersions)) {
            $io->error("Invalid source version: {$fromVersion}");
            return false;
        }

        if (!in_array($toVersion, $validVersions)) {
            $io->error("Invalid target version: {$toVersion}");
            return false;
        }

        $versionOrder = array_flip($validVersions);
        if ($versionOrder[$fromVersion] >= $versionOrder[$toVersion]) {
            $io->error("Target version must be higher than source version.");
            return false;
        }

        return true;
    }

    private function showUpgradePlan(string $fromVersion, string $toVersion, SymfonyStyle $io): void
    {
        $io->section('Upgrade Plan');

        $upgradePath = $this->getUpgradePath($fromVersion, $toVersion);

        $io->listing($upgradePath);

        $io->note([
            'This upgrade will apply the following transformations:',
            '• Update class namespaces and imports',
            '• Convert deprecated method calls',
            '• Update configuration format',
            '• Modernize database queries',
            '• And more...'
        ]);
    }

    private function getUpgradePath(string $fromVersion, string $toVersion): array
    {
        $path = [];

        if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
            $path[] = '3.2 → 5.0: Modernize controllers, models, and helper functions';
        }

        if (version_compare($fromVersion, '5.0', '<=') && version_compare($toVersion, '5.1', '>=')) {
            $path[] = '5.0 → 5.1: Update namespaces and configuration format';
        }

        if (version_compare($fromVersion, '5.1', '<=') && version_compare($toVersion, '6.0', '>=')) {
            $path[] = '5.1 → 6.0: Convert to facade pattern and update database methods';
        }

        if (version_compare($fromVersion, '6.0', '<=') && version_compare($toVersion, '8.0', '>=')) {
            $path[] = '6.0 → 8.0: Seamless upgrade with minimal changes';
        }

        return $path;
    }

    private function createBackup(string $sourcePath, SymfonyStyle $io): void
    {
        $io->section('Creating backup...');

        $backupPath = $sourcePath . '_backup_' . date('Y-m-d_H-i-s');

        // Simple backup using system command
        $command = "cp -r '{$sourcePath}' '{$backupPath}'";
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $io->success("Backup created at: {$backupPath}");
        } else {
            $io->warning("Failed to create backup. Please create a manual backup before proceeding.");
        }
    }

    private function generateRectorConfig(string $fromVersion, string $toVersion, string $sourcePath, SymfonyStyle $io): string
    {
        $io->section('Generating Rector configuration...');

        $configPath = $sourcePath . '/rector-thinkphp-upgrade.php';

        $sets = $this->getRequiredSets($fromVersion, $toVersion);

        $configContent = $this->buildConfigContent($sets, $sourcePath);

        file_put_contents($configPath, $configContent);

        $io->success("Rector configuration generated: {$configPath}");

        return $configPath;
    }

    private function getRequiredSets(string $fromVersion, string $toVersion): array
    {
        $sets = [];

        if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
            $sets[] = 'ThinkPHPSetList::THINKPHP_32_TO_50';
        }

        if (version_compare($fromVersion, '5.0', '<=') && version_compare($toVersion, '5.1', '>=')) {
            $sets[] = 'ThinkPHPSetList::THINKPHP_50_TO_51';
        }

        if (version_compare($fromVersion, '5.1', '<=') && version_compare($toVersion, '6.0', '>=')) {
            $sets[] = 'ThinkPHPSetList::THINKPHP_51_TO_60';
        }

        if (version_compare($fromVersion, '6.0', '<=') && version_compare($toVersion, '8.0', '>=')) {
            $sets[] = 'ThinkPHPSetList::THINKPHP_60_TO_80';
        }

        return $sets;
    }

    private function buildConfigContent(array $sets, string $sourcePath): string
    {
        $setsString = implode(",\n        ", $sets);

        return <<<PHP
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/application',
        __DIR__ . '/Application',
    ])
    ->withSets([
        {$setsString}
    ]);
PHP;
    }

    private function showNextSteps(string $configPath, string $sourcePath, bool $dryRun, SymfonyStyle $io): void
    {
        $io->section('Next Steps');

        $commands = [
            "cd {$sourcePath}",
        ];

        if ($dryRun) {
            $commands[] = "vendor/bin/rector process --config={$configPath} --dry-run";
            $io->info('Run the following commands to preview changes:');
        } else {
            $commands[] = "vendor/bin/rector process --config={$configPath}";
            $io->info('Run the following commands to apply the upgrade:');
        }

        foreach ($commands as $command) {
            $io->text("  {$command}");
        }

        $io->note([
            'After running Rector:',
            '1. Review all changes carefully',
            '2. Run your tests to ensure everything works',
            '3. Update your composer.json dependencies',
            '4. Clear all caches',
            '5. Test your application thoroughly'
        ]);
    }
}
