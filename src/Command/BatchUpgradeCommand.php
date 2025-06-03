<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Command;

use Rector\ThinkPHP\Service\BatchProjectManager;
use Rector\ThinkPHP\Service\PerformanceAnalyzer;
use Rector\ThinkPHP\Service\UpgradeReportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Batch upgrade command for multiple ThinkPHP projects
 */
final class BatchUpgradeCommand extends Command
{
    private BatchProjectManager $batchManager;
    private PerformanceAnalyzer $performanceAnalyzer;
    private UpgradeReportService $reportService;

    public function __construct()
    {
        parent::__construct();
        $this->batchManager = new BatchProjectManager();
        $this->performanceAnalyzer = new PerformanceAnalyzer();
        $this->reportService = new UpgradeReportService();
    }

    protected function configure(): void
    {
        $this->setName('thinkphp:batch-upgrade')
            ->setDescription('Batch upgrade multiple ThinkPHP projects')
            ->addArgument('config-file', InputArgument::REQUIRED, 'Path to batch upgrade configuration file')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would be upgraded without making changes')
            ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Number of parallel processes', 1)
            ->addOption('continue-on-error', 'c', InputOption::VALUE_NONE, 'Continue processing other projects if one fails')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Create backups before upgrading')
            ->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Delay between projects in seconds', 0)
            ->addOption('include-pattern', null, InputOption::VALUE_OPTIONAL, 'Include only projects matching this pattern')
            ->addOption('exclude-pattern', null, InputOption::VALUE_OPTIONAL, 'Exclude projects matching this pattern')
            ->addOption('report-format', null, InputOption::VALUE_OPTIONAL, 'Report format (json|html|markdown)', 'markdown')
            ->addOption('performance-analysis', null, InputOption::VALUE_NONE, 'Enable detailed performance analysis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('ThinkPHP Batch Upgrade Tool');

        // Load configuration
        $configFile = $input->getArgument('config-file');
        if (!file_exists($configFile)) {
            $io->error("Configuration file not found: {$configFile}");
            return Command::FAILURE;
        }

        $config = $this->loadConfiguration($configFile);
        if (!$config) {
            $io->error('Invalid configuration file format');
            return Command::FAILURE;
        }

        // Validate configuration
        $validationResult = $this->validateConfiguration($config);
        if (!$validationResult['valid']) {
            $io->error('Configuration validation failed:');
            foreach ($validationResult['errors'] as $error) {
                $io->text('• ' . $error);
            }
            return Command::FAILURE;
        }

        // Show summary
        $this->showBatchSummary($config, $input, $io);

        // Confirm execution
        if (!$input->getOption('dry-run') && !$io->confirm('Do you want to proceed with the batch upgrade?', false)) {
            $io->info('Batch upgrade cancelled.');
            return Command::SUCCESS;
        }

        // Start performance monitoring
        if ($input->getOption('performance-analysis')) {
            $this->performanceAnalyzer->startTimer('batch_upgrade');
        }

        // Execute batch upgrade
        $options = $this->buildUpgradeOptions($input);
        $results = $this->batchManager->batchUpgrade($config['projects'], $options);

        // Stop performance monitoring
        if ($input->getOption('performance-analysis')) {
            $this->performanceAnalyzer->stopTimer('batch_upgrade');
        }

        // Show results
        $this->showResults($results, $io);

        // Generate reports
        $this->generateReports($results, $input, $io);

        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function loadConfiguration(string $configFile): ?array
    {
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        
        try {
            switch ($extension) {
                case 'json':
                    $content = file_get_contents($configFile);
                    return json_decode($content, true);
                
                case 'yaml':
                case 'yml':
                    if (!function_exists('yaml_parse_file')) {
                        throw new \RuntimeException('YAML extension not available');
                    }
                    return yaml_parse_file($configFile);
                
                case 'php':
                    return include $configFile;
                
                default:
                    throw new \RuntimeException("Unsupported configuration format: {$extension}");
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function validateConfiguration(array $config): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check required sections
        if (!isset($config['projects'])) {
            $result['valid'] = false;
            $result['errors'][] = 'Missing "projects" section in configuration';
            return $result;
        }

        if (!is_array($config['projects']) || empty($config['projects'])) {
            $result['valid'] = false;
            $result['errors'][] = 'Projects section must be a non-empty array';
            return $result;
        }

        // Validate each project
        foreach ($config['projects'] as $projectName => $projectConfig) {
            $projectValidation = $this->validateProjectConfig($projectName, $projectConfig);
            if (!$projectValidation['valid']) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $projectValidation['errors']);
            }
            $result['warnings'] = array_merge($result['warnings'], $projectValidation['warnings']);
        }

        return $result;
    }

    private function validateProjectConfig(string $projectName, array $projectConfig): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check required fields
        $required = ['path', 'to_version'];
        foreach ($required as $field) {
            if (!isset($projectConfig[$field])) {
                $result['valid'] = false;
                $result['errors'][] = "Project '{$projectName}': Missing required field '{$field}'";
            }
        }

        // Check path exists
        if (isset($projectConfig['path']) && !is_dir($projectConfig['path'])) {
            $result['valid'] = false;
            $result['errors'][] = "Project '{$projectName}': Path does not exist: {$projectConfig['path']}";
        }

        // Check version format
        if (isset($projectConfig['to_version'])) {
            $validVersions = ['5.0', '5.1', '6.0', '8.0'];
            if (!in_array($projectConfig['to_version'], $validVersions)) {
                $result['warnings'][] = "Project '{$projectName}': Unusual target version: {$projectConfig['to_version']}";
            }
        }

        return $result;
    }

    private function showBatchSummary(array $config, InputInterface $input, SymfonyStyle $io): void
    {
        $io->section('Batch Upgrade Summary');

        $totalProjects = count($config['projects']);
        $io->text("Total projects: {$totalProjects}");

        // Group by target version
        $versionGroups = [];
        foreach ($config['projects'] as $projectName => $projectConfig) {
            $version = $projectConfig['to_version'];
            $versionGroups[$version] = ($versionGroups[$version] ?? 0) + 1;
        }

        $io->text('Target versions:');
        foreach ($versionGroups as $version => $count) {
            $io->text("  • ThinkPHP {$version}: {$count} projects");
        }

        // Show options
        $options = [];
        if ($input->getOption('dry-run')) {
            $options[] = 'Dry run mode';
        }
        if ($input->getOption('backup')) {
            $options[] = 'Create backups';
        }
        if ($input->getOption('continue-on-error')) {
            $options[] = 'Continue on errors';
        }
        if ($input->getOption('performance-analysis')) {
            $options[] = 'Performance analysis';
        }

        if (!empty($options)) {
            $io->text('Options: ' . implode(', ', $options));
        }
    }

    private function buildUpgradeOptions(InputInterface $input): array
    {
        return [
            'dry_run' => $input->getOption('dry-run'),
            'create_backup' => $input->getOption('backup'),
            'continue_on_errors' => $input->getOption('continue-on-error'),
            'delay_between_projects' => (int)$input->getOption('delay'),
            'include_pattern' => $input->getOption('include-pattern'),
            'exclude_pattern' => $input->getOption('exclude-pattern'),
            'performance_analysis' => $input->getOption('performance-analysis'),
        ];
    }

    private function showResults(array $results, SymfonyStyle $io): void
    {
        $io->section('Batch Upgrade Results');

        // Summary
        $io->text("Total projects: {$results['total_projects']}");
        $io->text("Successful: {$results['successful']}");
        $io->text("Failed: {$results['failed']}");
        $io->text("Skipped: {$results['skipped']}");
        $io->text("Duration: {$results['summary']['total_duration_formatted']}");
        $io->text("Success rate: {$results['summary']['success_rate']}%");

        // Detailed results
        if ($results['failed'] > 0) {
            $io->warning('Failed projects:');
            foreach ($results['projects'] as $projectName => $projectResult) {
                if (!$projectResult['success'] && !$projectResult['skipped']) {
                    $io->text("• {$projectName}:");
                    foreach ($projectResult['errors'] as $error) {
                        $io->text("  - {$error}");
                    }
                }
            }
        }

        if ($results['successful'] > 0) {
            $io->success('Successful projects:');
            foreach ($results['projects'] as $projectName => $projectResult) {
                if ($projectResult['success']) {
                    $duration = $this->formatDuration($projectResult['duration']);
                    $io->text("• {$projectName} ({$duration})");
                }
            }
        }

        // Recommendations
        if (!empty($results['summary']['recommendations'])) {
            $io->note('Recommendations:');
            foreach ($results['summary']['recommendations'] as $recommendation) {
                $io->text("• {$recommendation}");
            }
        }
    }

    private function generateReports(array $results, InputInterface $input, SymfonyStyle $io): void
    {
        $io->section('Generating Reports');

        $format = $input->getOption('report-format');
        $timestamp = date('Y-m-d_H-i-s');

        // Generate batch report
        $batchReport = $this->generateBatchReport($results);
        $batchReportPath = "batch_upgrade_report_{$timestamp}.{$format}";
        
        if ($this->saveReport($batchReport, $batchReportPath, $format)) {
            $io->success("Batch report saved: {$batchReportPath}");
        } else {
            $io->error("Failed to save batch report");
        }

        // Generate performance report if enabled
        if ($input->getOption('performance-analysis')) {
            $performanceReport = $this->performanceAnalyzer->generateReport();
            $performanceReportPath = "performance_report_{$timestamp}.json";
            
            if (file_put_contents($performanceReportPath, json_encode($performanceReport, JSON_PRETTY_PRINT))) {
                $io->success("Performance report saved: {$performanceReportPath}");
            } else {
                $io->error("Failed to save performance report");
            }
        }
    }

    private function generateBatchReport(array $results): string
    {
        $report = [];
        $report[] = "# ThinkPHP Batch Upgrade Report";
        $report[] = "";
        $report[] = "**Generated:** " . date('Y-m-d H:i:s');
        $report[] = "";

        // Summary
        $report[] = "## Summary";
        $report[] = "";
        $report[] = "- **Total Projects:** {$results['total_projects']}";
        $report[] = "- **Successful:** {$results['successful']}";
        $report[] = "- **Failed:** {$results['failed']}";
        $report[] = "- **Skipped:** {$results['skipped']}";
        $report[] = "- **Success Rate:** {$results['summary']['success_rate']}%";
        $report[] = "- **Total Duration:** {$results['summary']['total_duration_formatted']}";
        $report[] = "- **Average Duration:** {$results['summary']['average_duration']}s per project";
        $report[] = "";

        // Project Details
        $report[] = "## Project Details";
        $report[] = "";

        foreach ($results['projects'] as $projectName => $projectResult) {
            $status = $projectResult['success'] ? '✅ Success' : 
                     ($projectResult['skipped'] ? '⏭️ Skipped' : '❌ Failed');
            $duration = $this->formatDuration($projectResult['duration']);
            
            $report[] = "### {$projectName} - {$status}";
            $report[] = "";
            $report[] = "- **Duration:** {$duration}";
            
            if (!empty($projectResult['changes'])) {
                $report[] = "- **Changes:** " . count($projectResult['changes']);
                foreach (array_slice($projectResult['changes'], 0, 5) as $change) {
                    $report[] = "  - {$change}";
                }
                if (count($projectResult['changes']) > 5) {
                    $report[] = "  - ... and " . (count($projectResult['changes']) - 5) . " more";
                }
            }
            
            if (!empty($projectResult['errors'])) {
                $report[] = "- **Errors:**";
                foreach ($projectResult['errors'] as $error) {
                    $report[] = "  - {$error}";
                }
            }
            
            if (!empty($projectResult['warnings'])) {
                $report[] = "- **Warnings:**";
                foreach ($projectResult['warnings'] as $warning) {
                    $report[] = "  - {$warning}";
                }
            }
            
            $report[] = "";
        }

        // Recommendations
        if (!empty($results['summary']['recommendations'])) {
            $report[] = "## Recommendations";
            $report[] = "";
            foreach ($results['summary']['recommendations'] as $recommendation) {
                $report[] = "- {$recommendation}";
            }
            $report[] = "";
        }

        return implode("\n", $report);
    }

    private function saveReport(string $content, string $filePath, string $format): bool
    {
        switch ($format) {
            case 'json':
                // Convert markdown to structured data for JSON
                $data = [
                    'content' => $content,
                    'format' => 'markdown',
                    'generated_at' => date('c'),
                ];
                return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
            
            case 'html':
                // Convert markdown to HTML (simplified)
                $html = "<html><head><title>Batch Upgrade Report</title></head><body>";
                $html .= "<pre>" . htmlspecialchars($content) . "</pre>";
                $html .= "</body></html>";
                return file_put_contents($filePath, $html) !== false;
            
            case 'markdown':
            default:
                return file_put_contents($filePath, $content) !== false;
        }
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } else {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . $remainingSeconds . 's';
        }
    }
}
