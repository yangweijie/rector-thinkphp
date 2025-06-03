<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Plugin;

/**
 * Plugin manager for extensible ThinkPHP Rector functionality
 */
final class PluginManager
{
    /**
     * @var array<string, PluginInterface>
     */
    private array $plugins = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $pluginConfigs = [];

    /**
     * @var array<string, string>
     */
    private array $hooks = [];

    public function __construct()
    {
        $this->loadCorePlugins();
    }

    /**
     * Register a plugin
     */
    public function registerPlugin(string $name, PluginInterface $plugin, array $config = []): void
    {
        $this->plugins[$name] = $plugin;
        $this->pluginConfigs[$name] = $config;
        
        // Register plugin hooks
        $hooks = $plugin->getHooks();
        foreach ($hooks as $hook) {
            $this->hooks[$hook] = $name;
        }

        // Initialize plugin
        $plugin->initialize($config);
    }

    /**
     * Unregister a plugin
     */
    public function unregisterPlugin(string $name): void
    {
        if (isset($this->plugins[$name])) {
            $plugin = $this->plugins[$name];
            
            // Cleanup plugin hooks
            $hooks = $plugin->getHooks();
            foreach ($hooks as $hook) {
                if (isset($this->hooks[$hook]) && $this->hooks[$hook] === $name) {
                    unset($this->hooks[$hook]);
                }
            }

            // Cleanup plugin
            $plugin->cleanup();
            
            unset($this->plugins[$name]);
            unset($this->pluginConfigs[$name]);
        }
    }

    /**
     * Execute a hook with given data
     */
    public function executeHook(string $hook, array $data = []): array
    {
        $results = [];

        foreach ($this->plugins as $name => $plugin) {
            if (in_array($hook, $plugin->getHooks(), true)) {
                try {
                    $result = $plugin->executeHook($hook, $data);
                    $results[$name] = $result;
                } catch (\Exception $e) {
                    $results[$name] = [
                        'error' => $e->getMessage(),
                        'success' => false,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get all registered plugins
     */
    public function getPlugins(): array
    {
        return array_keys($this->plugins);
    }

    /**
     * Get plugin information
     */
    public function getPluginInfo(string $name): ?array
    {
        if (!isset($this->plugins[$name])) {
            return null;
        }

        $plugin = $this->plugins[$name];
        
        return [
            'name' => $name,
            'version' => $plugin->getVersion(),
            'description' => $plugin->getDescription(),
            'author' => $plugin->getAuthor(),
            'hooks' => $plugin->getHooks(),
            'config' => $this->pluginConfigs[$name] ?? [],
            'status' => 'active',
        ];
    }

    /**
     * Load plugins from directory
     */
    public function loadPluginsFromDirectory(string $directory): array
    {
        $loaded = [];
        
        if (!is_dir($directory)) {
            return $loaded;
        }

        $iterator = new \DirectoryIterator($directory);
        
        foreach ($iterator as $file) {
            if ($file->isDot() || !$file->isDir()) {
                continue;
            }

            $pluginPath = $file->getPathname();
            $pluginFile = $pluginPath . '/plugin.php';
            
            if (file_exists($pluginFile)) {
                try {
                    $pluginData = include $pluginFile;
                    
                    if (is_array($pluginData) && isset($pluginData['class'])) {
                        $pluginClass = $pluginData['class'];
                        
                        if (class_exists($pluginClass) && is_subclass_of($pluginClass, PluginInterface::class)) {
                            $plugin = new $pluginClass();
                            $config = $pluginData['config'] ?? [];
                            
                            $this->registerPlugin($file->getFilename(), $plugin, $config);
                            $loaded[] = $file->getFilename();
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue loading other plugins
                    error_log("Failed to load plugin from {$pluginPath}: " . $e->getMessage());
                }
            }
        }

        return $loaded;
    }

    /**
     * Install plugin from package
     */
    public function installPlugin(string $packagePath): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'plugin_name' => null,
        ];

        try {
            // Extract plugin package
            $extractPath = $this->extractPluginPackage($packagePath);
            
            // Validate plugin structure
            $validation = $this->validatePluginStructure($extractPath);
            if (!$validation['valid']) {
                $result['message'] = 'Invalid plugin structure: ' . implode(', ', $validation['errors']);
                return $result;
            }

            // Load plugin metadata
            $metadata = $this->loadPluginMetadata($extractPath);
            
            // Check dependencies
            $dependencyCheck = $this->checkPluginDependencies($metadata);
            if (!$dependencyCheck['satisfied']) {
                $result['message'] = 'Unsatisfied dependencies: ' . implode(', ', $dependencyCheck['missing']);
                return $result;
            }

            // Install plugin files
            $pluginName = $metadata['name'];
            $installPath = $this->getPluginInstallPath($pluginName);
            
            $this->copyPluginFiles($extractPath, $installPath);
            
            // Register plugin
            $pluginClass = $metadata['class'];
            $plugin = new $pluginClass();
            $this->registerPlugin($pluginName, $plugin, $metadata['config'] ?? []);

            $result['success'] = true;
            $result['message'] = "Plugin '{$pluginName}' installed successfully";
            $result['plugin_name'] = $pluginName;

        } catch (\Exception $e) {
            $result['message'] = 'Installation failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Uninstall plugin
     */
    public function uninstallPlugin(string $name): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            // Unregister plugin
            $this->unregisterPlugin($name);
            
            // Remove plugin files
            $installPath = $this->getPluginInstallPath($name);
            if (is_dir($installPath)) {
                $this->removeDirectory($installPath);
            }

            $result['success'] = true;
            $result['message'] = "Plugin '{$name}' uninstalled successfully";

        } catch (\Exception $e) {
            $result['message'] = 'Uninstallation failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get available plugin hooks
     */
    public function getAvailableHooks(): array
    {
        return [
            'before_upgrade' => 'Executed before starting upgrade process',
            'after_upgrade' => 'Executed after upgrade process completes',
            'before_file_transform' => 'Executed before transforming each file',
            'after_file_transform' => 'Executed after transforming each file',
            'before_config_migration' => 'Executed before migrating configuration',
            'after_config_migration' => 'Executed after migrating configuration',
            'before_dependency_update' => 'Executed before updating dependencies',
            'after_dependency_update' => 'Executed after updating dependencies',
            'on_error' => 'Executed when an error occurs',
            'on_warning' => 'Executed when a warning is generated',
            'custom_analysis' => 'Custom code analysis hook',
            'custom_transformation' => 'Custom code transformation hook',
        ];
    }

    /**
     * Create plugin template
     */
    public function createPluginTemplate(string $name, string $author, string $description): string
    {
        $template = <<<PHP
<?php

declare(strict_types=1);

namespace Rector\\ThinkPHP\\Plugin\\{$name};

use Rector\\ThinkPHP\\Plugin\\PluginInterface;

/**
 * {$description}
 * 
 * @author {$author}
 */
final class {$name}Plugin implements PluginInterface
{
    private array \$config = [];

    public function initialize(array \$config): void
    {
        \$this->config = \$config;
    }

    public function cleanup(): void
    {
        // Cleanup resources
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return '{$description}';
    }

    public function getAuthor(): string
    {
        return '{$author}';
    }

    public function getHooks(): array
    {
        return [
            'before_upgrade',
            'after_upgrade',
        ];
    }

    public function executeHook(string \$hook, array \$data): array
    {
        switch (\$hook) {
            case 'before_upgrade':
                return \$this->beforeUpgrade(\$data);
            
            case 'after_upgrade':
                return \$this->afterUpgrade(\$data);
            
            default:
                return ['success' => false, 'message' => 'Unknown hook'];
        }
    }

    private function beforeUpgrade(array \$data): array
    {
        // Implement your before upgrade logic here
        return ['success' => true, 'message' => 'Before upgrade hook executed'];
    }

    private function afterUpgrade(array \$data): array
    {
        // Implement your after upgrade logic here
        return ['success' => true, 'message' => 'After upgrade hook executed'];
    }
}
PHP;

        return $template;
    }

    /**
     * Load core plugins
     */
    private function loadCorePlugins(): void
    {
        // Register core plugins
        $this->registerPlugin('backup', new BackupPlugin());
        $this->registerPlugin('logger', new LoggerPlugin());
        $this->registerPlugin('notifier', new NotificationPlugin());
        $this->registerPlugin('validator', new ValidationPlugin());
    }

    private function extractPluginPackage(string $packagePath): string
    {
        // Implementation for extracting plugin package (ZIP, TAR, etc.)
        $extractPath = sys_get_temp_dir() . '/plugin_' . uniqid();
        
        if (pathinfo($packagePath, PATHINFO_EXTENSION) === 'zip') {
            $zip = new \ZipArchive();
            if ($zip->open($packagePath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();
            }
        }
        
        return $extractPath;
    }

    private function validatePluginStructure(string $pluginPath): array
    {
        $required = ['plugin.php', 'src/'];
        $errors = [];
        
        foreach ($required as $item) {
            if (!file_exists($pluginPath . '/' . $item)) {
                $errors[] = "Missing required file/directory: {$item}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private function loadPluginMetadata(string $pluginPath): array
    {
        $metadataFile = $pluginPath . '/plugin.php';
        return include $metadataFile;
    }

    private function checkPluginDependencies(array $metadata): array
    {
        $dependencies = $metadata['dependencies'] ?? [];
        $missing = [];
        
        foreach ($dependencies as $dependency => $version) {
            if (!$this->isDependencySatisfied($dependency, $version)) {
                $missing[] = "{$dependency} {$version}";
            }
        }
        
        return [
            'satisfied' => empty($missing),
            'missing' => $missing,
        ];
    }

    private function isDependencySatisfied(string $dependency, string $version): bool
    {
        // Check if dependency is satisfied
        // This could check for PHP extensions, Composer packages, etc.
        return true; // Simplified implementation
    }

    private function getPluginInstallPath(string $name): string
    {
        return __DIR__ . '/../../plugins/' . $name;
    }

    private function copyPluginFiles(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
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
}
