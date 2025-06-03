<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Helps with database migrations during ThinkPHP upgrades
 */
final class DatabaseMigrationHelper
{
    /**
     * @var array<string, array<string, string>> Database schema changes between versions
     */
    private const SCHEMA_CHANGES = [
        '3.2_to_5.0' => [
            'table_prefix' => 'think_',
            'charset' => 'utf8mb4',
            'engine' => 'InnoDB',
        ],
        '5.0_to_5.1' => [
            // Minimal changes
        ],
        '5.1_to_6.0' => [
            'session_table' => 'tp_session',
            'cache_table' => 'tp_cache',
        ],
        '6.0_to_8.0' => [
            // Minimal changes
        ],
    ];

    /**
     * @var array<string, array<string>> SQL scripts for version migrations
     */
    private const MIGRATION_SCRIPTS = [
        '3.2_to_5.0' => [
            'CREATE TABLE IF NOT EXISTS `tp_session` (
                `session_id` varchar(255) NOT NULL,
                `session_expire` int(11) NOT NULL,
                `session_data` blob,
                UNIQUE KEY `session_id` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            
            'CREATE TABLE IF NOT EXISTS `tp_cache` (
                `key` varchar(255) NOT NULL,
                `value` longtext,
                `expire` int(11) NOT NULL,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        ],
        '5.1_to_6.0' => [
            'ALTER TABLE `tp_session` MODIFY `session_data` longtext;',
            'ALTER TABLE `tp_cache` MODIFY `value` longtext;',
        ],
    ];

    public function analyzeDatabaseChanges(string $fromVersion, string $toVersion, array $dbConfig): array
    {
        $result = [
            'required_changes' => [],
            'optional_changes' => [],
            'warnings' => [],
            'sql_scripts' => [],
        ];

        $migrationKey = $fromVersion . '_to_' . $toVersion;

        // Check for schema changes
        if (isset(self::SCHEMA_CHANGES[$migrationKey])) {
            $changes = self::SCHEMA_CHANGES[$migrationKey];
            $result['required_changes'] = $this->analyzeSchemaChanges($changes, $dbConfig);
        }

        // Get SQL migration scripts
        if (isset(self::MIGRATION_SCRIPTS[$migrationKey])) {
            $result['sql_scripts'] = self::MIGRATION_SCRIPTS[$migrationKey];
        }

        // Analyze existing database structure
        $dbAnalysis = $this->analyzeExistingDatabase($dbConfig);
        $result['warnings'] = array_merge($result['warnings'], $dbAnalysis['warnings']);

        return $result;
    }

    public function generateMigrationPlan(string $fromVersion, string $toVersion, array $dbConfig): array
    {
        $plan = [
            'steps' => [],
            'estimated_time' => 0,
            'backup_required' => true,
            'downtime_required' => false,
        ];

        $changes = $this->analyzeDatabaseChanges($fromVersion, $toVersion, $dbConfig);

        // Step 1: Backup database
        $plan['steps'][] = [
            'type' => 'backup',
            'description' => 'Create database backup',
            'sql' => $this->generateBackupCommand($dbConfig),
            'estimated_time' => 60, // seconds
            'critical' => true,
        ];

        // Step 2: Apply schema changes
        foreach ($changes['sql_scripts'] as $index => $sql) {
            $plan['steps'][] = [
                'type' => 'schema_change',
                'description' => "Apply schema change #" . ($index + 1),
                'sql' => $sql,
                'estimated_time' => 10,
                'critical' => true,
            ];
        }

        // Step 3: Update configuration
        if (!empty($changes['required_changes'])) {
            $plan['steps'][] = [
                'type' => 'config_update',
                'description' => 'Update database configuration',
                'changes' => $changes['required_changes'],
                'estimated_time' => 5,
                'critical' => true,
            ];
        }

        // Step 4: Verify migration
        $plan['steps'][] = [
            'type' => 'verification',
            'description' => 'Verify database migration',
            'sql' => $this->generateVerificationQueries($toVersion),
            'estimated_time' => 30,
            'critical' => true,
        ];

        $plan['estimated_time'] = array_sum(array_column($plan['steps'], 'estimated_time'));

        return $plan;
    }

    public function executeMigrationPlan(array $plan, array $dbConfig, bool $dryRun = false): array
    {
        $result = [
            'success' => false,
            'executed_steps' => [],
            'failed_step' => null,
            'errors' => [],
            'warnings' => [],
        ];

        if ($dryRun) {
            $result['success'] = true;
            $result['executed_steps'] = array_map(function ($step) {
                return array_merge($step, ['status' => 'simulated']);
            }, $plan['steps']);
            return $result;
        }

        try {
            $pdo = $this->createDatabaseConnection($dbConfig);

            foreach ($plan['steps'] as $index => $step) {
                $stepResult = $this->executeStep($step, $pdo, $dbConfig);
                
                $result['executed_steps'][] = array_merge($step, [
                    'status' => $stepResult['success'] ? 'completed' : 'failed',
                    'execution_time' => $stepResult['execution_time'],
                    'output' => $stepResult['output'],
                ]);

                if (!$stepResult['success']) {
                    $result['failed_step'] = $index;
                    $result['errors'][] = $stepResult['error'];
                    break;
                }

                if (!empty($stepResult['warnings'])) {
                    $result['warnings'] = array_merge($result['warnings'], $stepResult['warnings']);
                }
            }

            $result['success'] = $result['failed_step'] === null;

        } catch (\Exception $e) {
            $result['errors'][] = 'Migration execution failed: ' . $e->getMessage();
        }

        return $result;
    }

    public function createDatabaseBackup(array $dbConfig, string $backupPath = null): array
    {
        $result = [
            'success' => false,
            'backup_path' => null,
            'error' => null,
        ];

        try {
            if ($backupPath === null) {
                $backupPath = sys_get_temp_dir() . '/thinkphp_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            }

            $command = $this->generateBackupCommand($dbConfig, $backupPath);
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $result['success'] = true;
                $result['backup_path'] = $backupPath;
            } else {
                $result['error'] = 'Backup command failed: ' . implode("\n", $output);
            }

        } catch (\Exception $e) {
            $result['error'] = 'Backup failed: ' . $e->getMessage();
        }

        return $result;
    }

    public function restoreDatabaseBackup(array $dbConfig, string $backupPath): array
    {
        $result = [
            'success' => false,
            'error' => null,
        ];

        try {
            if (!file_exists($backupPath)) {
                $result['error'] = 'Backup file not found: ' . $backupPath;
                return $result;
            }

            $command = sprintf(
                'mysql -h%s -P%s -u%s -p%s %s < %s',
                escapeshellarg($dbConfig['hostname']),
                escapeshellarg($dbConfig['hostport']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($backupPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $result['success'] = true;
            } else {
                $result['error'] = 'Restore command failed: ' . implode("\n", $output);
            }

        } catch (\Exception $e) {
            $result['error'] = 'Restore failed: ' . $e->getMessage();
        }

        return $result;
    }

    public function validateDatabaseStructure(array $dbConfig, string $version): array
    {
        $result = [
            'valid' => true,
            'issues' => [],
            'suggestions' => [],
        ];

        try {
            $pdo = $this->createDatabaseConnection($dbConfig);

            // Check required tables
            $requiredTables = $this->getRequiredTables($version);
            foreach ($requiredTables as $table) {
                if (!$this->tableExists($pdo, $table)) {
                    $result['valid'] = false;
                    $result['issues'][] = "Required table '{$table}' is missing";
                }
            }

            // Check table engines
            $tables = $this->getAllTables($pdo);
            foreach ($tables as $table) {
                $engine = $this->getTableEngine($pdo, $table);
                if ($engine !== 'InnoDB') {
                    $result['suggestions'][] = "Consider converting table '{$table}' to InnoDB engine";
                }
            }

            // Check charset
            $charset = $this->getDatabaseCharset($pdo);
            if (!in_array($charset, ['utf8mb4', 'utf8'])) {
                $result['suggestions'][] = "Consider using utf8mb4 charset for better Unicode support";
            }

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['issues'][] = 'Database validation failed: ' . $e->getMessage();
        }

        return $result;
    }

    private function analyzeSchemaChanges(array $changes, array $dbConfig): array
    {
        $requiredChanges = [];

        foreach ($changes as $key => $value) {
            switch ($key) {
                case 'table_prefix':
                    if (($dbConfig['prefix'] ?? '') !== $value) {
                        $requiredChanges[] = "Update table prefix to '{$value}'";
                    }
                    break;

                case 'charset':
                    $requiredChanges[] = "Ensure database charset is '{$value}'";
                    break;

                case 'engine':
                    $requiredChanges[] = "Ensure tables use '{$value}' engine";
                    break;
            }
        }

        return $requiredChanges;
    }

    private function analyzeExistingDatabase(array $dbConfig): array
    {
        $result = [
            'warnings' => [],
        ];

        try {
            $pdo = $this->createDatabaseConnection($dbConfig);

            // Check for large tables
            $largeTables = $this->findLargeTables($pdo);
            foreach ($largeTables as $table => $size) {
                $result['warnings'][] = "Large table '{$table}' ({$size}) may require longer migration time";
            }

            // Check for foreign keys
            $foreignKeys = $this->findForeignKeys($pdo);
            if (!empty($foreignKeys)) {
                $result['warnings'][] = 'Foreign key constraints found - migration may require careful ordering';
            }

        } catch (\Exception $e) {
            $result['warnings'][] = 'Could not analyze existing database: ' . $e->getMessage();
        }

        return $result;
    }

    private function createDatabaseConnection(array $dbConfig): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['hostname'],
            $dbConfig['hostport'],
            $dbConfig['database']
        );

        return new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    private function executeStep(array $step, \PDO $pdo, array $dbConfig): array
    {
        $result = [
            'success' => false,
            'execution_time' => 0,
            'output' => '',
            'error' => null,
            'warnings' => [],
        ];

        $startTime = microtime(true);

        try {
            switch ($step['type']) {
                case 'backup':
                    $backupResult = $this->createDatabaseBackup($dbConfig);
                    $result['success'] = $backupResult['success'];
                    $result['output'] = $backupResult['backup_path'] ?? '';
                    $result['error'] = $backupResult['error'];
                    break;

                case 'schema_change':
                    $pdo->exec($step['sql']);
                    $result['success'] = true;
                    $result['output'] = 'SQL executed successfully';
                    break;

                case 'config_update':
                    $result['success'] = true;
                    $result['output'] = 'Configuration changes noted';
                    break;

                case 'verification':
                    foreach ($step['sql'] as $query) {
                        $stmt = $pdo->query($query);
                        $stmt->fetchAll();
                    }
                    $result['success'] = true;
                    $result['output'] = 'Verification completed';
                    break;

                default:
                    $result['error'] = 'Unknown step type: ' . $step['type'];
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $result['execution_time'] = microtime(true) - $startTime;

        return $result;
    }

    private function generateBackupCommand(array $dbConfig, string $backupPath = null): string
    {
        if ($backupPath === null) {
            $backupPath = sys_get_temp_dir() . '/thinkphp_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
        }

        return sprintf(
            'mysqldump -h%s -P%s -u%s -p%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($dbConfig['hostname']),
            escapeshellarg($dbConfig['hostport']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($backupPath)
        );
    }

    /**
     * @return array<string>
     */
    private function generateVerificationQueries(string $version): array
    {
        $queries = [
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()',
        ];

        $requiredTables = $this->getRequiredTables($version);
        foreach ($requiredTables as $table) {
            $queries[] = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}'";
        }

        return $queries;
    }

    /**
     * @return array<string>
     */
    private function getRequiredTables(string $version): array
    {
        $tables = [
            '5.0' => ['tp_session'],
            '5.1' => ['tp_session'],
            '6.0' => ['tp_session', 'tp_cache'],
            '8.0' => ['tp_session', 'tp_cache'],
        ];

        return $tables[$version] ?? [];
    }

    private function tableExists(\PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tableName]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<string>
     */
    private function getAllTables(\PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
        
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getTableEngine(\PDO $pdo, string $tableName): string
    {
        $stmt = $pdo->prepare("SELECT engine FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tableName]);
        
        return $stmt->fetchColumn() ?: 'Unknown';
    }

    private function getDatabaseCharset(\PDO $pdo): string
    {
        $stmt = $pdo->query("SELECT default_character_set_name FROM information_schema.schemata WHERE schema_name = DATABASE()");
        
        return $stmt->fetchColumn() ?: 'Unknown';
    }

    /**
     * @return array<string, string>
     */
    private function findLargeTables(\PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT table_name, 
                   ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND (data_length + index_length) > 100 * 1024 * 1024
            ORDER BY (data_length + index_length) DESC
        ");

        $largeTables = [];
        while ($row = $stmt->fetch()) {
            $largeTables[$row['table_name']] = $row['size_mb'] . ' MB';
        }

        return $largeTables;
    }

    /**
     * @return array<string>
     */
    private function findForeignKeys(\PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT DISTINCT table_name
            FROM information_schema.key_column_usage
            WHERE table_schema = DATABASE()
            AND referenced_table_name IS NOT NULL
        ");

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
