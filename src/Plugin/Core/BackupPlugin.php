<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Plugin\Core;

use Rector\ThinkPHP\Plugin\PluginInterface;

/**
 * Core backup plugin for automatic project backups
 */
final class BackupPlugin implements PluginInterface
{
    private array $config = [];
    private string $backupDirectory = '';

    public function initialize(array $config): void
    {
        $this->config = array_merge([
            'backup_directory' => sys_get_temp_dir() . '/thinkphp-rector-backups',
            'compression' => true,
            'retention_days' => 30,
            'exclude_patterns' => [
                '*.log',
                'vendor/*',
                'node_modules/*',
                '.git/*',
                'runtime/*',
            ],
        ], $config);

        $this->backupDirectory = $this->config['backup_directory'];
        
        if (!is_dir($this->backupDirectory)) {
            mkdir($this->backupDirectory, 0755, true);
        }
    }

    public function cleanup(): void
    {
        $this->cleanupOldBackups();
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Automatic project backup plugin with compression and retention management';
    }

    public function getAuthor(): string
    {
        return 'ThinkPHP Rector Team';
    }

    public function getHooks(): array
    {
        return [
            'before_upgrade',
            'after_upgrade',
            'on_error',
        ];
    }

    public function executeHook(string $hook, array $data): array
    {
        switch ($hook) {
            case 'before_upgrade':
                return $this->createBackup($data);
            
            case 'after_upgrade':
                return $this->finalizeBackup($data);
            
            case 'on_error':
                return $this->handleError($data);
            
            default:
                return ['success' => false, 'message' => 'Unknown hook'];
        }
    }

    private function createBackup(array $data): array
    {
        $projectPath = $data['project_path'] ?? '';
        
        if (empty($projectPath) || !is_dir($projectPath)) {
            return ['success' => false, 'message' => 'Invalid project path'];
        }

        try {
            $backupName = $this->generateBackupName($projectPath);
            $backupPath = $this->backupDirectory . '/' . $backupName;
            
            // Create backup
            $this->copyProjectFiles($projectPath, $backupPath);
            
            // Compress if enabled
            if ($this->config['compression']) {
                $compressedPath = $this->compressBackup($backupPath);
                $this->removeDirectory($backupPath);
                $backupPath = $compressedPath;
            }

            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'backup_path' => $backupPath,
                'backup_size' => $this->getBackupSize($backupPath),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ];
        }
    }

    private function finalizeBackup(array $data): array
    {
        // Mark backup as successful
        $backupPath = $data['backup_path'] ?? '';
        
        if (!empty($backupPath) && file_exists($backupPath)) {
            $metadataFile = $backupPath . '.meta';
            $metadata = [
                'created_at' => date('c'),
                'project_path' => $data['project_path'] ?? '',
                'upgrade_success' => true,
                'backup_size' => $this->getBackupSize($backupPath),
            ];
            
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        }

        return ['success' => true, 'message' => 'Backup finalized'];
    }

    private function handleError(array $data): array
    {
        // Mark backup for potential restoration
        $backupPath = $data['backup_path'] ?? '';
        
        if (!empty($backupPath) && file_exists($backupPath)) {
            $metadataFile = $backupPath . '.meta';
            $metadata = [
                'created_at' => date('c'),
                'project_path' => $data['project_path'] ?? '',
                'upgrade_success' => false,
                'error' => $data['error'] ?? 'Unknown error',
                'restoration_available' => true,
            ];
            
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        }

        return ['success' => true, 'message' => 'Error handled, backup preserved for restoration'];
    }

    private function generateBackupName(string $projectPath): string
    {
        $projectName = basename($projectPath);
        $timestamp = date('Y-m-d_H-i-s');
        return "{$projectName}_backup_{$timestamp}";
    }

    private function copyProjectFiles(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            
            // Skip excluded patterns
            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $target = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    private function shouldExclude(string $path): bool
    {
        foreach ($this->config['exclude_patterns'] as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }

    private function compressBackup(string $backupPath): string
    {
        $compressedPath = $backupPath . '.tar.gz';
        
        $command = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($compressedPath),
            escapeshellarg($backupPath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Backup compression failed');
        }
        
        return $compressedPath;
    }

    private function getBackupSize(string $backupPath): string
    {
        if (is_file($backupPath)) {
            $bytes = filesize($backupPath);
        } else {
            $bytes = $this->getDirectorySize($backupPath);
        }
        
        return $this->formatBytes($bytes);
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

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    private function removeDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item);
            } else {
                unlink($item);
            }
        }

        rmdir($directory);
    }

    private function cleanupOldBackups(): void
    {
        $retentionDays = $this->config['retention_days'];
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);

        $iterator = new \DirectoryIterator($this->backupDirectory);

        foreach ($iterator as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->getMTime() < $cutoffTime) {
                if ($file->isDir()) {
                    $this->removeDirectory($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
        }
    }
}
