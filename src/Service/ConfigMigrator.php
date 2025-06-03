<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Migrates configuration files between ThinkPHP versions
 */
final class ConfigMigrator
{
    /**
     * @var array<string, array<string, string>> Version-specific config mappings
     */
    private const CONFIG_MAPPINGS = [
        '3.2_to_5.0' => [
            'DB_TYPE' => 'database.default',
            'DB_HOST' => 'database.connections.mysql.hostname',
            'DB_NAME' => 'database.connections.mysql.database',
            'DB_USER' => 'database.connections.mysql.username',
            'DB_PWD' => 'database.connections.mysql.password',
            'DB_PORT' => 'database.connections.mysql.hostport',
            'DB_PREFIX' => 'database.connections.mysql.prefix',
            'APP_DEBUG' => 'app.app_debug',
            'APP_TRACE' => 'app.app_trace',
            'DEFAULT_MODULE' => 'app.default_module',
            'DEFAULT_CONTROLLER' => 'app.default_controller',
            'DEFAULT_ACTION' => 'app.default_action',
            'URL_MODEL' => 'app.url_route_on',
            'SESSION_AUTO_START' => 'session.auto_start',
            'SESSION_OPTIONS' => 'session.options',
            'COOKIE_EXPIRE' => 'cookie.expire',
            'COOKIE_DOMAIN' => 'cookie.domain',
            'COOKIE_PATH' => 'cookie.path',
        ],
        '5.0_to_5.1' => [
            'app_debug' => 'app.app_debug',
            'app_trace' => 'app.app_trace',
            'app_status' => 'app.app_status',
            'auto_bind_module' => 'app.auto_bind_module',
            'default_return_type' => 'app.default_return_type',
            'default_module' => 'app.default_module',
            'default_controller' => 'app.default_controller',
            'default_action' => 'app.default_action',
            'url_route_on' => 'app.url_route_on',
            'pathinfo_depr' => 'app.pathinfo_depr',
        ],
        '5.1_to_6.0' => [
            // Most 5.1 configs are compatible with 6.0
            // Main changes are in structure, not key names
        ],
    ];

    /**
     * @var array<string, array<string, mixed>> Default configurations for each version
     */
    private const DEFAULT_CONFIGS = [
        '5.0' => [
            'app' => [
                'app_debug' => true,
                'app_trace' => false,
                'app_status' => '',
                'app_multi_module' => true,
                'auto_bind_module' => false,
                'default_return_type' => 'html',
                'default_module' => 'index',
                'default_controller' => 'Index',
                'default_action' => 'index',
                'url_route_on' => true,
                'url_route_must' => false,
                'url_convert' => true,
                'pathinfo_depr' => '/',
                'url_html_suffix' => 'html',
            ],
            'database' => [
                'type' => 'mysql',
                'hostname' => '127.0.0.1',
                'database' => '',
                'username' => 'root',
                'password' => '',
                'hostport' => '3306',
                'prefix' => '',
                'charset' => 'utf8',
                'debug' => true,
            ],
        ],
        '5.1' => [
            'app' => [
                'app_debug' => true,
                'app_trace' => false,
                'app_status' => '',
                'app_multi_module' => true,
                'auto_bind_module' => false,
                'default_return_type' => 'html',
                'default_module' => 'index',
                'default_controller' => 'Index',
                'default_action' => 'index',
                'url_route_on' => true,
                'url_route_must' => false,
                'url_convert' => true,
                'pathinfo_depr' => '/',
                'url_html_suffix' => 'html',
            ],
            'database' => [
                'type' => 'mysql',
                'hostname' => '127.0.0.1',
                'database' => '',
                'username' => 'root',
                'password' => '',
                'hostport' => '3306',
                'prefix' => '',
                'charset' => 'utf8',
                'debug' => true,
            ],
        ],
        '6.0' => [
            'app' => [
                'debug' => true,
                'trace' => false,
                'default_timezone' => 'Asia/Shanghai',
                'default_lang' => 'zh-cn',
                'default_module' => 'index',
                'deny_module_list' => ['common', 'runtime'],
                'controller_suffix' => false,
                'action_suffix' => false,
                'auto_bind_module' => false,
                'url_route_on' => true,
                'url_route_must' => false,
                'url_convert' => true,
                'pathinfo_depr' => '/',
                'url_html_suffix' => 'html',
            ],
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'type' => 'mysql',
                        'hostname' => '127.0.0.1',
                        'database' => '',
                        'username' => 'root',
                        'password' => '',
                        'hostport' => '3306',
                        'prefix' => '',
                        'charset' => 'utf8',
                        'debug' => true,
                    ],
                ],
            ],
        ],
    ];

    public function migrateConfig(string $fromVersion, string $toVersion, string $configPath): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'migrated_files' => [],
            'warnings' => [],
            'errors' => [],
        ];

        try {
            if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
                $this->migrate32To50($configPath, $result);
            }

            if (version_compare($fromVersion, '5.0', '<=') && version_compare($toVersion, '5.1', '>=')) {
                $this->migrate50To51($configPath, $result);
            }

            if (version_compare($fromVersion, '5.1', '<=') && version_compare($toVersion, '6.0', '>=')) {
                $this->migrate51To60($configPath, $result);
            }

            $result['success'] = true;
            $result['message'] = 'Configuration migration completed successfully';
        } catch (\Exception $e) {
            $result['errors'][] = 'Migration failed: ' . $e->getMessage();
        }

        return $result;
    }

    private function migrate32To50(string $configPath, array &$result): void
    {
        // Look for ThinkPHP 3.2 config files
        $oldConfigFile = $configPath . '/Application/Common/Conf/config.php';
        
        if (!file_exists($oldConfigFile)) {
            $result['warnings'][] = 'ThinkPHP 3.2 config file not found: ' . $oldConfigFile;
            return;
        }

        // Load old config
        $oldConfig = include $oldConfigFile;
        if (!is_array($oldConfig)) {
            $result['errors'][] = 'Invalid config file format: ' . $oldConfigFile;
            return;
        }

        // Create new config structure
        $newConfigs = $this->convertConfig32To50($oldConfig);

        // Create config directory
        $newConfigDir = $configPath . '/application';
        if (!is_dir($newConfigDir)) {
            mkdir($newConfigDir, 0755, true);
        }

        // Write new config files
        foreach ($newConfigs as $filename => $config) {
            $newConfigFile = $newConfigDir . '/' . $filename . '.php';
            $this->writeConfigFile($newConfigFile, $config);
            $result['migrated_files'][] = $newConfigFile;
        }
    }

    private function migrate50To51(string $configPath, array &$result): void
    {
        $configFiles = [
            'config.php' => 'app.php',
            'database.php' => 'database.php',
        ];

        $oldConfigDir = $configPath . '/application';
        $newConfigDir = $configPath . '/config';

        if (!is_dir($newConfigDir)) {
            mkdir($newConfigDir, 0755, true);
        }

        foreach ($configFiles as $oldFile => $newFile) {
            $oldPath = $oldConfigDir . '/' . $oldFile;
            $newPath = $newConfigDir . '/' . $newFile;

            if (file_exists($oldPath)) {
                $config = include $oldPath;
                if (is_array($config)) {
                    $convertedConfig = $this->convertConfig50To51($config, $oldFile);
                    $this->writeConfigFile($newPath, $convertedConfig);
                    $result['migrated_files'][] = $newPath;
                }
            }
        }
    }

    private function migrate51To60(string $configPath, array &$result): void
    {
        $configDir = $configPath . '/config';
        
        if (!is_dir($configDir)) {
            $result['warnings'][] = 'Config directory not found: ' . $configDir;
            return;
        }

        // Update app.php for 6.0 structure
        $appConfigFile = $configDir . '/app.php';
        if (file_exists($appConfigFile)) {
            $config = include $appConfigFile;
            if (is_array($config)) {
                $convertedConfig = $this->convertConfig51To60($config);
                $this->writeConfigFile($appConfigFile, $convertedConfig);
                $result['migrated_files'][] = $appConfigFile;
            }
        }

        // Update database.php for 6.0 structure
        $dbConfigFile = $configDir . '/database.php';
        if (file_exists($dbConfigFile)) {
            $config = include $dbConfigFile;
            if (is_array($config)) {
                $convertedConfig = $this->convertDatabase51To60($config);
                $this->writeConfigFile($dbConfigFile, $convertedConfig);
                $result['migrated_files'][] = $dbConfigFile;
            }
        }
    }

    private function convertConfig32To50(array $oldConfig): array
    {
        $newConfigs = [];

        // App config
        $appConfig = self::DEFAULT_CONFIGS['5.0']['app'];
        foreach (self::CONFIG_MAPPINGS['3.2_to_5.0'] as $oldKey => $newKey) {
            if (isset($oldConfig[$oldKey]) && str_starts_with($newKey, 'app.')) {
                $configKey = substr($newKey, 4); // Remove 'app.' prefix
                $appConfig[$configKey] = $oldConfig[$oldKey];
            }
        }
        $newConfigs['config'] = $appConfig;

        // Database config
        $dbConfig = self::DEFAULT_CONFIGS['5.0']['database'];
        foreach (self::CONFIG_MAPPINGS['3.2_to_5.0'] as $oldKey => $newKey) {
            if (isset($oldConfig[$oldKey]) && str_starts_with($newKey, 'database.')) {
                $configPath = explode('.', substr($newKey, 9)); // Remove 'database.' prefix
                $this->setNestedValue($dbConfig, $configPath, $oldConfig[$oldKey]);
            }
        }
        $newConfigs['database'] = $dbConfig;

        return $newConfigs;
    }

    private function convertConfig50To51(array $config, string $filename): array
    {
        if ($filename === 'config.php') {
            // Convert flat config to nested structure
            $newConfig = [];
            foreach ($config as $key => $value) {
                if (isset(self::CONFIG_MAPPINGS['5.0_to_5.1'][$key])) {
                    $newKey = self::CONFIG_MAPPINGS['5.0_to_5.1'][$key];
                    $this->setNestedValue($newConfig, explode('.', $newKey), $value);
                } else {
                    $newConfig[$key] = $value;
                }
            }
            return $newConfig;
        }

        return $config;
    }

    private function convertConfig51To60(array $config): array
    {
        // Update config structure for 6.0
        $newConfig = $config;

        // Rename some keys
        if (isset($config['app_debug'])) {
            $newConfig['debug'] = $config['app_debug'];
            unset($newConfig['app_debug']);
        }

        if (isset($config['app_trace'])) {
            $newConfig['trace'] = $config['app_trace'];
            unset($newConfig['app_trace']);
        }

        return $newConfig;
    }

    private function convertDatabase51To60(array $config): array
    {
        // Convert to 6.0 database structure
        if (!isset($config['connections'])) {
            $newConfig = [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => $config
                ]
            ];
            return $newConfig;
        }

        return $config;
    }

    private function setNestedValue(array &$array, array $keys, $value): void
    {
        $current = &$array;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    private function writeConfigFile(string $filePath, array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        
        // Format the output for better readability
        $content = str_replace('array (', '[', $content);
        $content = str_replace(')', ']', $content);
        $content = preg_replace('/=>\s*\[/', ' => [', $content);
        $content = preg_replace('/\s*\],\s*\]/', "\n    ],\n]", $content);
        
        file_put_contents($filePath, $content);
    }

    public function validateConfig(string $configPath, string $version): array
    {
        $result = [
            'valid' => true,
            'issues' => [],
            'suggestions' => [],
        ];

        $requiredFiles = $this->getRequiredConfigFiles($version);
        
        foreach ($requiredFiles as $file) {
            $filePath = $configPath . '/' . $file;
            if (!file_exists($filePath)) {
                $result['valid'] = false;
                $result['issues'][] = "Missing required config file: {$file}";
            } else {
                $config = include $filePath;
                if (!is_array($config)) {
                    $result['valid'] = false;
                    $result['issues'][] = "Invalid config file format: {$file}";
                }
            }
        }

        return $result;
    }

    private function getRequiredConfigFiles(string $version): array
    {
        $files = [
            '5.0' => ['config.php', 'database.php'],
            '5.1' => ['app.php', 'database.php'],
            '6.0' => ['app.php', 'database.php'],
            '8.0' => ['app.php', 'database.php'],
        ];

        return $files[$version] ?? [];
    }
}
