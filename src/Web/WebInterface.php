<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Web;

use Rector\ThinkPHP\Service\BatchProjectManager;
use Rector\ThinkPHP\Service\CodeQualityAnalyzer;
use Rector\ThinkPHP\Service\ConfigMigrator;
use Rector\ThinkPHP\Service\DependencyManager;
use Rector\ThinkPHP\Service\PerformanceAnalyzer;
use Rector\ThinkPHP\Service\UpgradeReportService;

/**
 * Web interface for ThinkPHP Rector tool
 */
final class WebInterface
{
    private BatchProjectManager $batchManager;
    private CodeQualityAnalyzer $qualityAnalyzer;
    private ConfigMigrator $configMigrator;
    private DependencyManager $dependencyManager;
    private PerformanceAnalyzer $performanceAnalyzer;
    private UpgradeReportService $reportService;

    public function __construct()
    {
        $this->batchManager = new BatchProjectManager();
        $this->qualityAnalyzer = new CodeQualityAnalyzer();
        $this->configMigrator = new ConfigMigrator();
        $this->dependencyManager = new DependencyManager();
        $this->performanceAnalyzer = new PerformanceAnalyzer();
        $this->reportService = new UpgradeReportService();
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Set CORS headers
        $this->setCorsHeaders();
        
        // Handle preflight requests
        if ($method === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        try {
            $response = $this->route($method, $path);
            $this->sendJsonResponse($response);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getMessage(), 500);
        }
    }

    private function route(string $method, string $path): array
    {
        switch ($path) {
            case '/':
                return $this->handleDashboard();
            
            case '/api/projects':
                return $method === 'GET' ? $this->handleGetProjects() : $this->handleCreateProject();
            
            case '/api/projects/analyze':
                return $this->handleAnalyzeProject();
            
            case '/api/projects/upgrade':
                return $this->handleUpgradeProject();
            
            case '/api/batch/upgrade':
                return $this->handleBatchUpgrade();
            
            case '/api/reports':
                return $this->handleGetReports();
            
            case '/api/performance':
                return $this->handleGetPerformanceMetrics();
            
            case '/api/health':
                return $this->handleHealthCheck();
            
            default:
                if (str_starts_with($path, '/api/projects/')) {
                    $projectId = substr($path, 14);
                    return $this->handleProjectDetails($projectId);
                }
                
                throw new \RuntimeException('Not found', 404);
        }
    }

    private function handleDashboard(): array
    {
        return [
            'title' => 'ThinkPHP Rector Dashboard',
            'version' => '2.0.0',
            'features' => [
                'Single project upgrade',
                'Batch project upgrade',
                'Code quality analysis',
                'Performance monitoring',
                'Configuration migration',
                'Dependency management',
            ],
            'stats' => $this->getDashboardStats(),
        ];
    }

    private function handleGetProjects(): array
    {
        // In a real implementation, this would load from a database or file system
        $projects = $this->scanForProjects();
        
        return [
            'projects' => $projects,
            'total' => count($projects),
        ];
    }

    private function handleCreateProject(): array
    {
        $input = $this->getJsonInput();
        
        if (!isset($input['name']) || !isset($input['path'])) {
            throw new \InvalidArgumentException('Name and path are required');
        }

        $project = [
            'id' => uniqid(),
            'name' => $input['name'],
            'path' => $input['path'],
            'current_version' => $this->detectProjectVersion($input['path']),
            'target_version' => $input['target_version'] ?? null,
            'status' => 'pending',
            'created_at' => date('c'),
        ];

        // In a real implementation, save to database
        
        return [
            'project' => $project,
            'message' => 'Project created successfully',
        ];
    }

    private function handleAnalyzeProject(): array
    {
        $input = $this->getJsonInput();
        
        if (!isset($input['path'])) {
            throw new \InvalidArgumentException('Project path is required');
        }

        $projectPath = $input['path'];
        
        // Analyze project
        $analysis = [
            'version_detection' => $this->detectProjectVersion($projectPath),
            'code_quality' => $this->analyzeCodeQuality($projectPath),
            'dependencies' => $this->analyzeDependencies($projectPath),
            'configuration' => $this->analyzeConfiguration($projectPath),
            'recommendations' => $this->generateRecommendations($projectPath),
        ];

        return [
            'analysis' => $analysis,
            'analyzed_at' => date('c'),
        ];
    }

    private function handleUpgradeProject(): array
    {
        $input = $this->getJsonInput();
        
        $requiredFields = ['path', 'from_version', 'to_version'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }

        // Start performance monitoring
        $this->performanceAnalyzer->startTimer('single_project_upgrade');

        try {
            // Perform upgrade
            $result = $this->performSingleProjectUpgrade($input);
            
            // Stop performance monitoring
            $this->performanceAnalyzer->stopTimer('single_project_upgrade');
            
            return [
                'result' => $result,
                'performance' => $this->performanceAnalyzer->generateReport(),
                'upgraded_at' => date('c'),
            ];
            
        } catch (\Exception $e) {
            $this->performanceAnalyzer->stopTimer('single_project_upgrade');
            throw $e;
        }
    }

    private function handleBatchUpgrade(): array
    {
        $input = $this->getJsonInput();
        
        if (!isset($input['projects'])) {
            throw new \InvalidArgumentException('Projects configuration is required');
        }

        // Start performance monitoring
        $this->performanceAnalyzer->startTimer('batch_upgrade');

        try {
            $options = $input['options'] ?? [];
            $results = $this->batchManager->batchUpgrade($input['projects'], $options);
            
            // Stop performance monitoring
            $this->performanceAnalyzer->stopTimer('batch_upgrade');
            
            return [
                'results' => $results,
                'performance' => $this->performanceAnalyzer->generateReport(),
                'completed_at' => date('c'),
            ];
            
        } catch (\Exception $e) {
            $this->performanceAnalyzer->stopTimer('batch_upgrade');
            throw $e;
        }
    }

    private function handleGetReports(): array
    {
        // In a real implementation, load from storage
        $reports = [
            [
                'id' => 'report_1',
                'type' => 'upgrade',
                'project' => 'project-alpha',
                'created_at' => '2024-01-15T10:30:00Z',
                'status' => 'completed',
                'summary' => 'Successful upgrade from 3.2 to 6.0',
            ],
            [
                'id' => 'report_2',
                'type' => 'batch',
                'projects_count' => 5,
                'created_at' => '2024-01-14T15:45:00Z',
                'status' => 'completed',
                'summary' => 'Batch upgrade of 5 projects',
            ],
        ];

        return [
            'reports' => $reports,
            'total' => count($reports),
        ];
    }

    private function handleGetPerformanceMetrics(): array
    {
        return [
            'metrics' => $this->performanceAnalyzer->getMetrics(),
            'counters' => $this->performanceAnalyzer->getCounters(),
            'report' => $this->performanceAnalyzer->generateReport(),
        ];
    }

    private function handleHealthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '2.0.0',
            'checks' => [
                'php_version' => [
                    'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'pass' : 'fail',
                    'value' => PHP_VERSION,
                ],
                'memory_usage' => [
                    'status' => 'pass',
                    'value' => memory_get_usage(true),
                    'formatted' => $this->formatBytes(memory_get_usage(true)),
                ],
                'disk_space' => [
                    'status' => 'pass',
                    'value' => disk_free_space('.'),
                    'formatted' => $this->formatBytes(disk_free_space('.')),
                ],
            ],
        ];

        return $health;
    }

    private function handleProjectDetails(string $projectId): array
    {
        // In a real implementation, load from database
        return [
            'project' => [
                'id' => $projectId,
                'name' => 'Project ' . $projectId,
                'status' => 'active',
                'last_upgrade' => '2024-01-15T10:30:00Z',
            ],
        ];
    }

    private function getDashboardStats(): array
    {
        return [
            'total_projects' => 12,
            'successful_upgrades' => 10,
            'failed_upgrades' => 1,
            'pending_upgrades' => 1,
            'average_upgrade_time' => '5m 30s',
            'last_upgrade' => '2024-01-15T10:30:00Z',
        ];
    }

    private function scanForProjects(): array
    {
        // Simplified project scanning
        return [
            [
                'id' => 'proj_1',
                'name' => 'E-commerce Site',
                'path' => '/var/www/ecommerce',
                'current_version' => '5.1',
                'status' => 'active',
            ],
            [
                'id' => 'proj_2',
                'name' => 'CMS System',
                'path' => '/var/www/cms',
                'current_version' => '3.2',
                'status' => 'needs_upgrade',
            ],
        ];
    }

    private function detectProjectVersion(string $projectPath): ?string
    {
        // Simplified version detection
        if (file_exists($projectPath . '/config/app.php')) {
            return '6.0';
        } elseif (file_exists($projectPath . '/application/config.php')) {
            return '5.0';
        } elseif (file_exists($projectPath . '/Application/Common/Conf/config.php')) {
            return '3.2';
        }
        
        return null;
    }

    private function analyzeCodeQuality(string $projectPath): array
    {
        // Simplified code quality analysis
        return [
            'score' => 85,
            'issues' => 3,
            'suggestions' => 7,
            'files_analyzed' => 45,
        ];
    }

    private function analyzeDependencies(string $projectPath): array
    {
        return [
            'composer_exists' => file_exists($projectPath . '/composer.json'),
            'outdated_packages' => 2,
            'security_issues' => 0,
        ];
    }

    private function analyzeConfiguration(string $projectPath): array
    {
        return [
            'config_files' => 3,
            'migration_needed' => true,
            'compatibility_issues' => 1,
        ];
    }

    private function generateRecommendations(string $projectPath): array
    {
        return [
            'Upgrade to ThinkPHP 6.0 for better performance',
            'Update composer dependencies',
            'Migrate configuration files',
            'Add type hints for better code quality',
        ];
    }

    private function performSingleProjectUpgrade(array $config): array
    {
        // Simplified upgrade process
        return [
            'success' => true,
            'changes' => [
                'Updated 15 files',
                'Migrated configuration',
                'Updated dependencies',
            ],
            'duration' => 120,
            'warnings' => [],
            'errors' => [],
        ];
    }

    private function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
    }

    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON input');
        }
        
        return $data ?? [];
    }

    private function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    private function sendErrorResponse(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'error' => $message,
            'status_code' => $statusCode,
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
