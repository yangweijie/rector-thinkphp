<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Plugin;

/**
 * Interface for ThinkPHP Rector plugins
 */
interface PluginInterface
{
    /**
     * Initialize the plugin with configuration
     */
    public function initialize(array $config): void;

    /**
     * Cleanup plugin resources
     */
    public function cleanup(): void;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Get plugin description
     */
    public function getDescription(): string;

    /**
     * Get plugin author
     */
    public function getAuthor(): string;

    /**
     * Get list of hooks this plugin supports
     */
    public function getHooks(): array;

    /**
     * Execute a specific hook
     */
    public function executeHook(string $hook, array $data): array;
}
