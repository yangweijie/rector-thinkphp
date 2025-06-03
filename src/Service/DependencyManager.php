<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Manages composer.json dependencies during ThinkPHP upgrades
 */
final class DependencyManager
{
    /**
     * @var array<string, array<string, string>> Version-specific dependencies
     */
    private const VERSION_DEPENDENCIES = [
        '5.0' => [
            'require' => [
                'topthink/framework' => '^5.0.0',
                'topthink/think-installer' => '^1.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^6.0',
            ],
        ],
        '5.1' => [
            'require' => [
                'topthink/framework' => '^5.1.0',
                'topthink/think-installer' => '^2.0',
                'topthink/think-captcha' => '^2.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^7.0',
            ],
        ],
        '6.0' => [
            'require' => [
                'topthink/framework' => '^6.0.0',
                'topthink/think-orm' => '^2.0',
                'topthink/think-view' => '^1.0',
                'topthink/think-captcha' => '^3.0',
                'topthink/think-multi-app' => '^1.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^9.0',
                'mockery/mockery' => '^1.3.1',
            ],
        ],
        '8.0' => [
            'require' => [
                'topthink/framework' => '^8.0.0',
                'topthink/think-orm' => '^3.0',
                'topthink/think-view' => '^2.0',
                'topthink/think-captcha' => '^4.0',
                'topthink/think-multi-app' => '^1.0',
                'topthink/think-filesystem' => '^1.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^10.0',
                'mockery/mockery' => '^1.5',
                'symfony/var-dumper' => '^6.0',
            ],
        ],
    ];

    /**
     * @var array<string, array<string>> Deprecated packages to remove
     */
    private const DEPRECATED_PACKAGES = [
        '5.0' => [],
        '5.1' => [
            'topthink/think-testing',
        ],
        '6.0' => [
            'topthink/think-installer',
            'topthink/think-testing',
        ],
        '8.0' => [
            'topthink/think-installer',
            'topthink/think-testing',
        ],
    ];

    /**
     * @var array<string, array<string, string>> PHP version requirements
     */
    private const PHP_REQUIREMENTS = [
        '5.0' => ['php' => '>=5.6.0'],
        '5.1' => ['php' => '>=7.1.0'],
        '6.0' => ['php' => '>=7.2.5'],
        '8.0' => ['php' => '>=8.0.0'],
    ];

    public function updateComposerJson(string $projectPath, string $toVersion): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'changes' => [],
            'warnings' => [],
            'errors' => [],
        ];

        $composerFile = $projectPath . '/composer.json';
        
        try {
            // Load existing composer.json
            $composer = $this->loadComposerJson($composerFile);
            
            // Backup original
            $this->backupComposerJson($composerFile);
            
            // Update dependencies
            $this->updateDependencies($composer, $toVersion, $result);
            
            // Update PHP requirements
            $this->updatePhpRequirements($composer, $toVersion, $result);
            
            // Remove deprecated packages
            $this->removeDeprecatedPackages($composer, $toVersion, $result);
            
            // Update autoload configuration
            $this->updateAutoload($composer, $toVersion, $result);
            
            // Update scripts
            $this->updateScripts($composer, $toVersion, $result);
            
            // Save updated composer.json
            $this->saveComposerJson($composerFile, $composer);
            
            $result['success'] = true;
            $result['message'] = 'composer.json updated successfully';
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Failed to update composer.json: ' . $e->getMessage();
        }

        return $result;
    }

    private function loadComposerJson(string $filePath): array
    {
        if (!file_exists($filePath)) {
            // Create basic composer.json if it doesn't exist
            return [
                'name' => 'thinkphp/project',
                'description' => 'ThinkPHP project',
                'type' => 'project',
                'require' => [],
                'require-dev' => [],
                'autoload' => [],
                'config' => [
                    'preferred-install' => 'dist',
                ],
            ];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Cannot read composer.json');
        }

        $composer = json_decode($content, true);
        if ($composer === null) {
            throw new \RuntimeException('Invalid JSON in composer.json');
        }

        return $composer;
    }

    private function backupComposerJson(string $filePath): void
    {
        if (file_exists($filePath)) {
            $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
            copy($filePath, $backupPath);
        }
    }

    private function updateDependencies(array &$composer, string $toVersion, array &$result): void
    {
        if (!isset(self::VERSION_DEPENDENCIES[$toVersion])) {
            $result['warnings'][] = "No dependency information available for version {$toVersion}";
            return;
        }

        $versionDeps = self::VERSION_DEPENDENCIES[$toVersion];

        // Update require dependencies
        if (isset($versionDeps['require'])) {
            if (!isset($composer['require'])) {
                $composer['require'] = [];
            }

            foreach ($versionDeps['require'] as $package => $version) {
                $oldVersion = $composer['require'][$package] ?? 'not installed';
                $composer['require'][$package] = $version;
                $result['changes'][] = "Updated {$package}: {$oldVersion} → {$version}";
            }
        }

        // Update require-dev dependencies
        if (isset($versionDeps['require-dev'])) {
            if (!isset($composer['require-dev'])) {
                $composer['require-dev'] = [];
            }

            foreach ($versionDeps['require-dev'] as $package => $version) {
                $oldVersion = $composer['require-dev'][$package] ?? 'not installed';
                $composer['require-dev'][$package] = $version;
                $result['changes'][] = "Updated dev dependency {$package}: {$oldVersion} → {$version}";
            }
        }
    }

    private function updatePhpRequirements(array &$composer, string $toVersion, array &$result): void
    {
        if (!isset(self::PHP_REQUIREMENTS[$toVersion])) {
            return;
        }

        $phpReq = self::PHP_REQUIREMENTS[$toVersion];
        
        if (!isset($composer['require'])) {
            $composer['require'] = [];
        }

        foreach ($phpReq as $package => $version) {
            $oldVersion = $composer['require'][$package] ?? 'not specified';
            $composer['require'][$package] = $version;
            $result['changes'][] = "Updated {$package} requirement: {$oldVersion} → {$version}";
        }
    }

    private function removeDeprecatedPackages(array &$composer, string $toVersion, array &$result): void
    {
        if (!isset(self::DEPRECATED_PACKAGES[$toVersion])) {
            return;
        }

        $deprecated = self::DEPRECATED_PACKAGES[$toVersion];

        foreach (['require', 'require-dev'] as $section) {
            if (!isset($composer[$section])) {
                continue;
            }

            foreach ($deprecated as $package) {
                if (isset($composer[$section][$package])) {
                    unset($composer[$section][$package]);
                    $result['changes'][] = "Removed deprecated package: {$package}";
                }
            }
        }
    }

    private function updateAutoload(array &$composer, string $toVersion, array &$result): void
    {
        if (!isset($composer['autoload'])) {
            $composer['autoload'] = [];
        }

        // Update autoload configuration based on version
        if (version_compare($toVersion, '6.0', '>=')) {
            // ThinkPHP 6.0+ uses app namespace
            $composer['autoload']['psr-4'] = [
                'app\\' => 'app/',
            ];
            $result['changes'][] = 'Updated autoload configuration for ThinkPHP 6.0+';
        } elseif (version_compare($toVersion, '5.0', '>=')) {
            // ThinkPHP 5.x uses application namespace
            $composer['autoload']['psr-4'] = [
                'app\\' => 'application/',
            ];
            $result['changes'][] = 'Updated autoload configuration for ThinkPHP 5.x';
        }

        // Add autoload-dev for tests
        if (!isset($composer['autoload-dev'])) {
            $composer['autoload-dev'] = [];
        }

        $composer['autoload-dev']['psr-4'] = [
            'tests\\' => 'tests/',
        ];
    }

    private function updateScripts(array &$composer, string $toVersion, array &$result): void
    {
        if (!isset($composer['scripts'])) {
            $composer['scripts'] = [];
        }

        // Add common scripts
        $scripts = [
            'test' => 'phpunit',
            'test-coverage' => 'phpunit --coverage-html coverage',
        ];

        if (version_compare($toVersion, '6.0', '>=')) {
            $scripts['post-autoload-dump'] = [
                '@php think service:discover',
                '@php think vendor:publish',
            ];
        }

        foreach ($scripts as $name => $script) {
            if (!isset($composer['scripts'][$name])) {
                $composer['scripts'][$name] = $script;
                $result['changes'][] = "Added script: {$name}";
            }
        }
    }

    private function saveComposerJson(string $filePath, array $composer): void
    {
        $content = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($content === false) {
            throw new \RuntimeException('Failed to encode composer.json');
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException('Failed to write composer.json');
        }
    }

    public function generateInstallInstructions(string $toVersion): array
    {
        $instructions = [
            'title' => "Composer Update Instructions for ThinkPHP {$toVersion}",
            'steps' => [],
        ];

        $instructions['steps'][] = [
            'title' => 'Update Dependencies',
            'command' => 'composer update',
            'description' => 'Update all dependencies to their latest compatible versions',
        ];

        $instructions['steps'][] = [
            'title' => 'Install New Dependencies',
            'command' => 'composer install --no-dev --optimize-autoloader',
            'description' => 'Install production dependencies with optimized autoloader',
        ];

        if (version_compare($toVersion, '6.0', '>=')) {
            $instructions['steps'][] = [
                'title' => 'Discover Services',
                'command' => 'php think service:discover',
                'description' => 'Discover and register services',
            ];

            $instructions['steps'][] = [
                'title' => 'Publish Vendor Assets',
                'command' => 'php think vendor:publish',
                'description' => 'Publish vendor assets and configurations',
            ];
        }

        $instructions['steps'][] = [
            'title' => 'Clear Cache',
            'command' => 'php think clear',
            'description' => 'Clear all application caches',
        ];

        return $instructions;
    }

    public function validateDependencies(string $projectPath, string $version): array
    {
        $result = [
            'valid' => true,
            'issues' => [],
            'suggestions' => [],
        ];

        $composerFile = $projectPath . '/composer.json';
        
        if (!file_exists($composerFile)) {
            $result['valid'] = false;
            $result['issues'][] = 'composer.json not found';
            return $result;
        }

        try {
            $composer = $this->loadComposerJson($composerFile);
            
            // Check required packages
            if (isset(self::VERSION_DEPENDENCIES[$version]['require'])) {
                foreach (self::VERSION_DEPENDENCIES[$version]['require'] as $package => $requiredVersion) {
                    if (!isset($composer['require'][$package])) {
                        $result['valid'] = false;
                        $result['issues'][] = "Missing required package: {$package}";
                    }
                }
            }

            // Check PHP version
            if (isset(self::PHP_REQUIREMENTS[$version]['php'])) {
                $requiredPhp = self::PHP_REQUIREMENTS[$version]['php'];
                if (!isset($composer['require']['php'])) {
                    $result['suggestions'][] = "Add PHP version requirement: {$requiredPhp}";
                }
            }

            // Check for deprecated packages
            if (isset(self::DEPRECATED_PACKAGES[$version])) {
                foreach (self::DEPRECATED_PACKAGES[$version] as $package) {
                    if (isset($composer['require'][$package]) || isset($composer['require-dev'][$package])) {
                        $result['suggestions'][] = "Remove deprecated package: {$package}";
                    }
                }
            }

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['issues'][] = 'Error reading composer.json: ' . $e->getMessage();
        }

        return $result;
    }
}
