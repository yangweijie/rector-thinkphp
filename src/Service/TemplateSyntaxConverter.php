<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Converts template syntax between ThinkPHP versions
 */
final class TemplateSyntaxConverter
{
    /**
     * @var array<string, array<string, string>> Template syntax mappings
     */
    private const SYNTAX_MAPPINGS = [
        '3.2_to_5.0' => [
            // Variable output
            '{$var}' => '{$var}',
            '{$var|default="默认值"}' => '{$var|default="默认值"}',
            
            // Function calls
            '{:function()}' => '{:function()}',
            '{:date("Y-m-d", $time)}' => '{:date("Y-m-d", $time)}',
            
            // Include templates
            '<include file="header" />' => '{include file="header"}',
            '<include file="Public:header" />' => '{include file="public/header"}',
            
            // Volist loops
            '<volist name="list" id="vo">' => '{volist name="list" id="vo"}',
            '</volist>' => '{/volist}',
            
            // If conditions
            '<if condition="$user">' => '{if condition="$user"}',
            '<else />' => '{else}',
            '</if>' => '{/if}',
            
            // Switch statements
            '<switch name="status">' => '{switch name="status"}',
            '<case value="1">' => '{case value="1"}',
            '<default />' => '{default}',
            '</switch>' => '{/switch}',
            
            // Load CSS/JS
            '<load file="css/style.css" />' => '{load file="css/style.css"}',
            '<load file="js/common.js" />' => '{load file="js/common.js"}',
        ],
        '5.0_to_5.1' => [
            // Most syntax remains the same, minor adjustments
            '{$Think.config.app_debug}' => '{$Think.APP_DEBUG}',
            '{$Think.const.MODULE_NAME}' => '{$Think.MODULE_NAME}',
        ],
        '5.1_to_6.0' => [
            // Template inheritance changes
            '<extend name="layout" />' => '{extend name="layout"}',
            '<block name="content">' => '{block name="content"}',
            '</block>' => '{/block}',
            
            // Asset loading
            '{load file="/static/css/style.css"}' => '{load file="/static/css/style.css"}',
            
            // URL generation
            '{:url("index/index")}' => '{:url("index/index")}',
            '{:Url("index/index")}' => '{:url("index/index")}',
        ],
    ];

    /**
     * @var array<string, array<string, string>> File extension mappings
     */
    private const FILE_EXTENSIONS = [
        '3.2' => '.html',
        '5.0' => '.html',
        '5.1' => '.html',
        '6.0' => '.html',
        '8.0' => '.html',
    ];

    /**
     * @var array<string, string> Template directory mappings
     */
    private const TEMPLATE_DIRECTORIES = [
        '3.2' => 'Application/{Module}/View',
        '5.0' => 'application/{module}/view',
        '5.1' => 'application/{module}/view',
        '6.0' => 'app/{module}/view',
        '8.0' => 'app/{module}/view',
    ];

    public function convertTemplates(string $projectPath, string $fromVersion, string $toVersion): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'converted_files' => [],
            'warnings' => [],
            'errors' => [],
        ];

        try {
            // Find template files
            $templateFiles = $this->findTemplateFiles($projectPath, $fromVersion);
            
            if (empty($templateFiles)) {
                $result['warnings'][] = 'No template files found';
                $result['success'] = true;
                return $result;
            }

            // Convert each template file
            foreach ($templateFiles as $templateFile) {
                $this->convertTemplateFile($templateFile, $fromVersion, $toVersion, $result);
            }

            // Move template files if directory structure changed
            $this->moveTemplateFiles($projectPath, $fromVersion, $toVersion, $result);

            $result['success'] = true;
            $result['message'] = 'Template conversion completed';

        } catch (\Exception $e) {
            $result['errors'][] = 'Template conversion failed: ' . $e->getMessage();
        }

        return $result;
    }

    private function findTemplateFiles(string $projectPath, string $fromVersion): array
    {
        $templateFiles = [];
        $templateDir = $this->getTemplateDirectory($projectPath, $fromVersion);
        
        if (!is_dir($templateDir)) {
            return $templateFiles;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($templateDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'html') {
                $templateFiles[] = $file->getPathname();
            }
        }

        return $templateFiles;
    }

    private function getTemplateDirectory(string $projectPath, string $version): string
    {
        $pattern = self::TEMPLATE_DIRECTORIES[$version] ?? 'app/view';
        
        // Handle module placeholder
        if (str_contains($pattern, '{module}') || str_contains($pattern, '{Module}')) {
            // For now, assume 'index' module - in real implementation,
            // we would scan for all modules
            $pattern = str_replace(['{module}', '{Module}'], ['index', 'Index'], $pattern);
        }

        return $projectPath . '/' . $pattern;
    }

    private function convertTemplateFile(string $filePath, string $fromVersion, string $toVersion, array &$result): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            $result['errors'][] = "Cannot read template file: {$filePath}";
            return;
        }

        $originalContent = $content;
        
        // Apply syntax conversions
        $content = $this->applySyntaxConversions($content, $fromVersion, $toVersion);
        
        // Apply additional transformations
        $content = $this->applyAdditionalTransformations($content, $fromVersion, $toVersion);

        // Only write if content changed
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) !== false) {
                $result['converted_files'][] = $filePath;
            } else {
                $result['errors'][] = "Cannot write template file: {$filePath}";
            }
        }
    }

    private function applySyntaxConversions(string $content, string $fromVersion, string $toVersion): string
    {
        $mappingKey = $fromVersion . '_to_' . $toVersion;
        
        if (!isset(self::SYNTAX_MAPPINGS[$mappingKey])) {
            return $content;
        }

        $mappings = self::SYNTAX_MAPPINGS[$mappingKey];
        
        foreach ($mappings as $oldSyntax => $newSyntax) {
            $content = str_replace($oldSyntax, $newSyntax, $content);
        }

        return $content;
    }

    private function applyAdditionalTransformations(string $content, string $fromVersion, string $toVersion): string
    {
        // Convert XML-style tags to bracket-style tags (3.2 to 5.0+)
        if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
            $content = $this->convertXmlToBracketSyntax($content);
        }

        // Update asset paths
        $content = $this->updateAssetPaths($content, $fromVersion, $toVersion);

        // Update URL generation
        $content = $this->updateUrlGeneration($content, $fromVersion, $toVersion);

        return $content;
    }

    private function convertXmlToBracketSyntax(string $content): string
    {
        // Convert self-closing tags
        $patterns = [
            '/<include\s+file="([^"]+)"\s*\/?>/' => '{include file="$1"}',
            '/<load\s+file="([^"]+)"\s*\/?>/' => '{load file="$1"}',
            '/<else\s*\/?>/' => '{else}',
            '/<default\s*\/?>/' => '{default}',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Convert paired tags
        $pairedTags = [
            'volist' => ['<volist\s+name="([^"]+)"\s+id="([^"]+)">', '{volist name="$1" id="$2"}', '</volist>', '{/volist}'],
            'if' => ['<if\s+condition="([^"]+)">', '{if condition="$1"}', '</if>', '{/if}'],
            'switch' => ['<switch\s+name="([^"]+)">', '{switch name="$1"}', '</switch>', '{/switch}'],
            'case' => ['<case\s+value="([^"]+)">', '{case value="$1"}', '</case>', '{/case}'],
            'block' => ['<block\s+name="([^"]+)">', '{block name="$1"}', '</block>', '{/block}'],
        ];

        foreach ($pairedTags as $tag => [$openPattern, $openReplacement, $closePattern, $closeReplacement]) {
            $content = preg_replace('/' . $openPattern . '/', $openReplacement, $content);
            $content = str_replace($closePattern, $closeReplacement, $content);
        }

        return $content;
    }

    private function updateAssetPaths(string $content, string $fromVersion, string $toVersion): string
    {
        // Update asset paths based on version changes
        if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
            // Update public path references
            $content = str_replace('__PUBLIC__', '/static', $content);
            $content = str_replace('__CSS__', '/static/css', $content);
            $content = str_replace('__JS__', '/static/js', $content);
            $content = str_replace('__IMG__', '/static/img', $content);
        }

        return $content;
    }

    private function updateUrlGeneration(string $content, string $fromVersion, string $toVersion): string
    {
        // Update URL generation functions
        if (version_compare($toVersion, '5.1', '>=')) {
            // Convert U() function to url() function
            $content = preg_replace('/\{:U\(([^)]+)\)\}/', '{:url($1)}', $content);
        }

        return $content;
    }

    private function moveTemplateFiles(string $projectPath, string $fromVersion, string $toVersion, array &$result): void
    {
        $oldDir = $this->getTemplateDirectory($projectPath, $fromVersion);
        $newDir = $this->getTemplateDirectory($projectPath, $toVersion);

        if ($oldDir === $newDir || !is_dir($oldDir)) {
            return;
        }

        // Create new directory structure
        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
        }

        // Move template files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($oldDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($oldDir, '', $file->getPathname());
                $newPath = $newDir . $relativePath;
                
                $newDirPath = dirname($newPath);
                if (!is_dir($newDirPath)) {
                    mkdir($newDirPath, 0755, true);
                }

                if (copy($file->getPathname(), $newPath)) {
                    $result['converted_files'][] = "Moved: {$file->getPathname()} → {$newPath}";
                }
            }
        }
    }

    public function validateTemplates(string $projectPath, string $version): array
    {
        $result = [
            'valid' => true,
            'issues' => [],
            'suggestions' => [],
        ];

        $templateDir = $this->getTemplateDirectory($projectPath, $version);
        
        if (!is_dir($templateDir)) {
            $result['issues'][] = "Template directory not found: {$templateDir}";
            $result['valid'] = false;
            return $result;
        }

        $templateFiles = $this->findTemplateFiles($projectPath, $version);
        
        foreach ($templateFiles as $templateFile) {
            $this->validateTemplateFile($templateFile, $version, $result);
        }

        return $result;
    }

    private function validateTemplateFile(string $filePath, string $version, array &$result): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            $result['issues'][] = "Cannot read template file: {$filePath}";
            return;
        }

        // Check for deprecated syntax
        $this->checkDeprecatedSyntax($content, $version, $filePath, $result);
        
        // Check for missing includes
        $this->checkMissingIncludes($content, $filePath, $result);
        
        // Check for broken asset references
        $this->checkAssetReferences($content, $filePath, $result);
    }

    private function checkDeprecatedSyntax(string $content, string $version, string $filePath, array &$result): void
    {
        $deprecatedPatterns = [
            '5.0' => [
                '/<\w+[^>]*\/?>/' => 'XML-style template tags are deprecated, use bracket syntax instead',
            ],
            '6.0' => [
                '/\{:U\([^)]+\)\}/' => 'U() function is deprecated, use url() instead',
            ],
        ];

        if (isset($deprecatedPatterns[$version])) {
            foreach ($deprecatedPatterns[$version] as $pattern => $message) {
                if (preg_match($pattern, $content)) {
                    $result['suggestions'][] = "In {$filePath}: {$message}";
                }
            }
        }
    }

    private function checkMissingIncludes(string $content, string $filePath, array &$result): void
    {
        // Check for include statements that might reference missing files
        preg_match_all('/\{include\s+file="([^"]+)"\}/', $content, $matches);
        
        foreach ($matches[1] as $includeFile) {
            // This is a simplified check - real implementation would resolve the actual path
            if (str_contains($includeFile, 'missing') || str_contains($includeFile, 'notfound')) {
                $result['issues'][] = "Possible missing include in {$filePath}: {$includeFile}";
            }
        }
    }

    private function checkAssetReferences(string $content, string $filePath, array &$result): void
    {
        // Check for asset references that might be broken
        $assetPatterns = [
            '/src="([^"]+)"/',
            '/href="([^"]+)"/',
            '/url\([\'"]([^\'"]+)[\'"]\)/',
        ];

        foreach ($assetPatterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            foreach ($matches[1] as $assetPath) {
                if (str_contains($assetPath, '__') && !str_contains($assetPath, '/static')) {
                    $result['suggestions'][] = "Update asset path in {$filePath}: {$assetPath}";
                }
            }
        }
    }
}
