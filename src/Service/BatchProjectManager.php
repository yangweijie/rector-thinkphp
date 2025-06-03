<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manages batch upgrades for multiple ThinkPHP projects
 */
final class BatchProjectManager
{
    private UpgradeReportService $reportService;
    private CodeQualityAnalyzer $qualityAnalyzer;
    private ConfigMigrator $configMigrator;
    private DependencyManager $dependencyManager;

    public function __construct()
    {
        $this->reportService = new UpgradeReportService();
        $this->qualityAnalyzer = new CodeQualityAnalyzer();
        $this->configMigrator = new ConfigMigrator();
        $this->dependencyManager = new DependencyManager();
    }

    /**
     * @param array<string, array<string, mixed>> $projects
     */
    public function batchUpgrade(array $projects, array $options = []): array
    {
        $results = [
            'total_projects' => count($projects),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'projects' => [],
            'summary' => [],
            'start_time' => time(),
            'end_time' => null,
        ];

        foreach ($projects as $projectName => $projectConfig) {
            $projectResult = $this->upgradeProject($projectName, $projectConfig, $options);
            $results['projects'][$projectName] = $projectResult;

            if ($projectResult['success']) {
                $results['successful']++;
            } elseif ($projectResult['skipped']) {
                $results['skipped']++;
            } else {
                $results['failed']++;
            }

            // Add delay between projects if specified
            if (isset($options['delay_between_projects'])) {
                sleep($options['delay_between_projects']);
            }
        }

        $results['end_time'] = time();
        $results['duration'] = $results['end_time'] - $results['start_time'];
        $results['summary'] = $this->generateBatchSummary($results);

        return $results;
    }

    /**
     * @param array<string, mixed> $projectConfig
     * @param array<string, mixed> $options
     */
    private function upgradeProject(string $projectName, array $projectConfig, array $options): array
    {
        $result = [
            'project_name' => $projectName,
            'success' => false,
            'skipped' => false,
            'errors' => [],
            'warnings' => [],
            'changes' => [],
            'start_time' => time(),
            'end_time' => null,
            'duration' => 0,
        ];

        try {
            // Validate project configuration
            if (!$this->validateProjectConfig($projectConfig)) {
                $result['errors'][] = 'Invalid project configuration';
                return $result;
            }

            $projectPath = $projectConfig['path'];
            $fromVersion = $projectConfig['from_version'] ?? 'auto';
            $toVersion = $projectConfig['to_version'];

            // Check if project should be skipped
            if ($this->shouldSkipProject($projectPath, $projectConfig, $options)) {
                $result['skipped'] = true;
                $result['warnings'][] = 'Project skipped based on criteria';
                return $result;
            }

            // Auto-detect version if needed
            if ($fromVersion === 'auto') {
                $fromVersion = $this->detectProjectVersion($projectPath);
                if (!$fromVersion) {
                    $result['errors'][] = 'Could not detect ThinkPHP version';
                    return $result;
                }
            }

            // Perform pre-upgrade checks
            $preCheckResult = $this->performPreUpgradeChecks($projectPath, $fromVersion, $toVersion);
            if (!$preCheckResult['passed']) {
                $result['errors'] = array_merge($result['errors'], $preCheckResult['errors']);
                if (!($options['continue_on_errors'] ?? false)) {
                    return $result;
                }
            }

            // Create backup if requested
            if ($options['create_backup'] ?? true) {
                $backupResult = $this->createProjectBackup($projectPath, $projectName);
                if (!$backupResult['success']) {
                    $result['warnings'][] = 'Failed to create backup: ' . $backupResult['error'];
                }
            }

            // Perform the upgrade
            $upgradeResult = $this->performProjectUpgrade($projectPath, $fromVersion, $toVersion, $options);
            
            $result['changes'] = $upgradeResult['changes'];
            $result['warnings'] = array_merge($result['warnings'], $upgradeResult['warnings']);

            if ($upgradeResult['success']) {
                // Perform post-upgrade validation
                $validationResult = $this->performPostUpgradeValidation($projectPath, $toVersion);
                if ($validationResult['passed']) {
                    $result['success'] = true;
                } else {
                    $result['errors'] = array_merge($result['errors'], $validationResult['errors']);
                }
            } else {
                $result['errors'] = array_merge($result['errors'], $upgradeResult['errors']);
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Unexpected error: ' . $e->getMessage();
        } finally {
            $result['end_time'] = time();
            $result['duration'] = $result['end_time'] - $result['start_time'];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $projectConfig
     */
    private function validateProjectConfig(array $projectConfig): bool
    {
        $required = ['path', 'to_version'];
        
        foreach ($required as $field) {
            if (!isset($projectConfig[$field])) {
                return false;
            }
        }

        return is_dir($projectConfig['path']);
    }

    /**
     * @param array<string, mixed> $projectConfig
     * @param array<string, mixed> $options
     */
    private function shouldSkipProject(string $projectPath, array $projectConfig, array $options): bool
    {
        // Skip if project has skip flag
        if ($projectConfig['skip'] ?? false) {
            return true;
        }

        // Skip if project doesn't match include pattern
        if (isset($options['include_pattern'])) {
            if (!preg_match($options['include_pattern'], $projectPath)) {
                return true;
            }
        }

        // Skip if project matches exclude pattern
        if (isset($options['exclude_pattern'])) {
            if (preg_match($options['exclude_pattern'], $projectPath)) {
                return true;
            }
        }

        // Skip if project is already at target version
        if (isset($options['skip_if_current'])) {
            $currentVersion = $this->detectProjectVersion($projectPath);
            if ($currentVersion === $projectConfig['to_version']) {
                return true;
            }
        }

        return false;
    }

    private function detectProjectVersion(string $projectPath): ?string
    {
        // Implementation similar to UpgradeWizardCommand::detectThinkPHPVersion
        $indicators = [
            '3.2' => ['ThinkPHP/ThinkPHP.php', 'Application/Common/Conf/config.php'],
            '5.0' => ['thinkphp/start.php', 'application/config.php'],
            '5.1' => ['config/app.php', 'config/database.php'],
            '6.0' => ['app/controller', 'config/app.php', 'composer.json'],
        ];

        foreach ($indicators as $version => $files) {
            $found = true;
            foreach ($files as $file) {
                if (!file_exists($projectPath . '/' . $file)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $version;
            }
        }

        return null;
    }

    private function performPreUpgradeChecks(string $projectPath, string $fromVersion, string $toVersion): array
    {
        $result = [
            'passed' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check PHP version compatibility
        $phpVersion = PHP_VERSION;
        $requiredPhp = $this->getRequiredPhpVersion($toVersion);
        if (version_compare($phpVersion, $requiredPhp, '<')) {
            $result['passed'] = false;
            $result['errors'][] = "PHP {$requiredPhp}+ required for ThinkPHP {$toVersion}, current: {$phpVersion}";
        }

        // Check disk space
        $freeSpace = disk_free_space($projectPath);
        $projectSize = $this->getDirectorySize($projectPath);
        if ($freeSpace < $projectSize * 2) {
            $result['warnings'][] = 'Low disk space - recommend at least 2x project size free';
        }

        // Check write permissions
        if (!is_writable($projectPath)) {
            $result['passed'] = false;
            $result['errors'][] = 'Project directory is not writable';
        }

        // Check for uncommitted changes (if git repository)
        if (is_dir($projectPath . '/.git')) {
            $gitStatus = shell_exec("cd {$projectPath} && git status --porcelain");
            if (!empty(trim($gitStatus))) {
                $result['warnings'][] = 'Project has uncommitted changes';
            }
        }

        return $result;
    }

    private function createProjectBackup(string $projectPath, string $projectName): array
    {
        $backupDir = dirname($projectPath) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupPath = $backupDir . '/' . $projectName . '_backup_' . date('Y-m-d_H-i-s');
        
        $command = "cp -r '{$projectPath}' '{$backupPath}'";
        exec($command, $output, $returnCode);

        return [
            'success' => $returnCode === 0,
            'backup_path' => $backupPath,
            'error' => $returnCode !== 0 ? 'Backup command failed' : null,
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function performProjectUpgrade(string $projectPath, string $fromVersion, string $toVersion, array $options): array
    {
        $result = [
            'success' => false,
            'changes' => [],
            'warnings' => [],
            'errors' => [],
        ];

        try {
            // Migrate configurations
            $configResult = $this->configMigrator->migrateConfig($fromVersion, $toVersion, $projectPath);
            if ($configResult['success']) {
                $result['changes'] = array_merge($result['changes'], $configResult['migrated_files']);
            } else {
                $result['errors'] = array_merge($result['errors'], $configResult['errors']);
            }

            // Update dependencies
            $depResult = $this->dependencyManager->updateComposerJson($projectPath, $toVersion);
            if ($depResult['success']) {
                $result['changes'] = array_merge($result['changes'], $depResult['changes']);
            } else {
                $result['errors'] = array_merge($result['errors'], $depResult['errors']);
            }

            // Run Rector transformations
            $rectorResult = $this->runRectorForProject($projectPath, $fromVersion, $toVersion, $options);
            if ($rectorResult['success']) {
                $result['changes'] = array_merge($result['changes'], $rectorResult['changes']);
                $result['success'] = true;
            } else {
                $result['errors'] = array_merge($result['errors'], $rectorResult['errors']);
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Upgrade failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runRectorForProject(string $projectPath, string $fromVersion, string $toVersion, array $options): array
    {
        // Generate temporary Rector config
        $configPath = $this->generateTempRectorConfig($fromVersion, $toVersion, $projectPath);
        
        $command = "cd {$projectPath} && vendor/bin/rector process --config={$configPath}";
        if ($options['dry_run'] ?? false) {
            $command .= ' --dry-run';
        }

        exec($command, $output, $returnCode);

        // Clean up temp config
        if (file_exists($configPath)) {
            unlink($configPath);
        }

        return [
            'success' => $returnCode === 0,
            'changes' => $returnCode === 0 ? ['Rector transformations applied'] : [],
            'errors' => $returnCode !== 0 ? ['Rector execution failed'] : [],
            'output' => implode("\n", $output),
        ];
    }

    private function performPostUpgradeValidation(string $projectPath, string $toVersion): array
    {
        $result = [
            'passed' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check for syntax errors
        $syntaxCheck = $this->checkPhpSyntax($projectPath);
        if (!$syntaxCheck['passed']) {
            $result['passed'] = false;
            $result['errors'] = array_merge($result['errors'], $syntaxCheck['errors']);
        }

        // Validate configuration files
        $configValidation = $this->configMigrator->validateConfig($projectPath, $toVersion);
        if (!$configValidation['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $configValidation['issues']);
        }

        // Validate dependencies
        $depValidation = $this->dependencyManager->validateDependencies($projectPath, $toVersion);
        if (!$depValidation['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $depValidation['issues']);
        }

        return $result;
    }

    private function checkPhpSyntax(string $projectPath): array
    {
        $result = [
            'passed' => true,
            'errors' => [],
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $output = shell_exec("php -l {$file->getPathname()} 2>&1");
                if (strpos($output, 'No syntax errors') === false) {
                    $result['passed'] = false;
                    $result['errors'][] = "Syntax error in {$file->getPathname()}: {$output}";
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateBatchSummary(array $results): array
    {
        return [
            'success_rate' => $results['total_projects'] > 0 ? 
                round(($results['successful'] / $results['total_projects']) * 100, 2) : 0,
            'average_duration' => $results['total_projects'] > 0 ? 
                round($results['duration'] / $results['total_projects'], 2) : 0,
            'total_duration_formatted' => $this->formatDuration($results['duration']),
            'recommendations' => $this->generateRecommendations($results),
        ];
    }

    /**
     * @param array<string, mixed> $results
     * @return array<string>
     */
    private function generateRecommendations(array $results): array
    {
        $recommendations = [];

        if ($results['failed'] > 0) {
            $recommendations[] = 'Review failed projects and address common issues';
        }

        if ($results['successful'] > 0) {
            $recommendations[] = 'Run tests on successfully upgraded projects';
        }

        $avgDuration = $results['summary']['average_duration'] ?? 0;
        if ($avgDuration > 300) { // 5 minutes
            $recommendations[] = 'Consider optimizing upgrade process for better performance';
        }

        return $recommendations;
    }

    private function getRequiredPhpVersion(string $thinkphpVersion): string
    {
        $requirements = [
            '5.0' => '5.6.0',
            '5.1' => '7.1.0',
            '6.0' => '7.2.5',
            '8.0' => '8.0.0',
        ];

        return $requirements[$thinkphpVersion] ?? '7.4.0';
    }

    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function generateTempRectorConfig(string $fromVersion, string $toVersion, string $projectPath): string
    {
        $sets = $this->getRequiredSets($fromVersion, $toVersion);
        $setsString = implode(",\n        ", $sets);
        
        $configContent = <<<PHP
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

        $tempPath = sys_get_temp_dir() . '/rector_' . uniqid() . '.php';
        file_put_contents($tempPath, $configContent);
        
        return $tempPath;
    }

    /**
     * @return array<string>
     */
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

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}
