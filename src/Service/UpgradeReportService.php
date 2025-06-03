<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Service for generating upgrade reports
 */
final class UpgradeReportService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $changes = [];

    /**
     * @var array<string>
     */
    private array $warnings = [];

    /**
     * @var array<string>
     */
    private array $errors = [];

    public function addChange(string $file, string $type, string $description, array $details = []): void
    {
        if (!isset($this->changes[$file])) {
            $this->changes[$file] = [];
        }

        $this->changes[$file][] = [
            'type' => $type,
            'description' => $description,
            'details' => $details,
            'timestamp' => time(),
        ];
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function generateReport(string $fromVersion, string $toVersion): string
    {
        $report = [];
        $report[] = "# ThinkPHP Upgrade Report";
        $report[] = "";
        $report[] = "**Upgrade Path:** {$fromVersion} → {$toVersion}";
        $report[] = "**Generated:** " . date('Y-m-d H:i:s');
        $report[] = "";

        // Summary
        $totalChanges = array_sum(array_map('count', $this->changes));
        $report[] = "## Summary";
        $report[] = "";
        $report[] = "- **Files Modified:** " . count($this->changes);
        $report[] = "- **Total Changes:** {$totalChanges}";
        $report[] = "- **Warnings:** " . count($this->warnings);
        $report[] = "- **Errors:** " . count($this->errors);
        $report[] = "";

        // Changes by type
        $changesByType = $this->groupChangesByType();
        if (!empty($changesByType)) {
            $report[] = "## Changes by Type";
            $report[] = "";
            foreach ($changesByType as $type => $count) {
                $report[] = "- **{$type}:** {$count}";
            }
            $report[] = "";
        }

        // Detailed changes
        if (!empty($this->changes)) {
            $report[] = "## Detailed Changes";
            $report[] = "";
            foreach ($this->changes as $file => $fileChanges) {
                $report[] = "### {$file}";
                $report[] = "";
                foreach ($fileChanges as $change) {
                    $report[] = "- **{$change['type']}:** {$change['description']}";
                    if (!empty($change['details'])) {
                        foreach ($change['details'] as $key => $value) {
                            $report[] = "  - {$key}: {$value}";
                        }
                    }
                }
                $report[] = "";
            }
        }

        // Warnings
        if (!empty($this->warnings)) {
            $report[] = "## Warnings";
            $report[] = "";
            foreach ($this->warnings as $warning) {
                $report[] = "⚠️ {$warning}";
            }
            $report[] = "";
        }

        // Errors
        if (!empty($this->errors)) {
            $report[] = "## Errors";
            $report[] = "";
            foreach ($this->errors as $error) {
                $report[] = "❌ {$error}";
            }
            $report[] = "";
        }

        // Next steps
        $report[] = "## Next Steps";
        $report[] = "";
        $report[] = "1. **Review Changes:** Carefully review all the changes made to your code";
        $report[] = "2. **Update Dependencies:** Update your composer.json with new ThinkPHP version";
        $report[] = "3. **Run Tests:** Execute your test suite to ensure everything works";
        $report[] = "4. **Clear Caches:** Clear all application caches";
        $report[] = "5. **Manual Review:** Some changes may require manual intervention";
        $report[] = "";

        // Version-specific notes
        $versionNotes = $this->getVersionSpecificNotes($fromVersion, $toVersion);
        if (!empty($versionNotes)) {
            $report[] = "## Version-Specific Notes";
            $report[] = "";
            foreach ($versionNotes as $note) {
                $report[] = "- {$note}";
            }
            $report[] = "";
        }

        return implode("\n", $report);
    }

    /**
     * @return array<string, int>
     */
    private function groupChangesByType(): array
    {
        $types = [];
        foreach ($this->changes as $fileChanges) {
            foreach ($fileChanges as $change) {
                $type = $change['type'];
                $types[$type] = ($types[$type] ?? 0) + 1;
            }
        }
        return $types;
    }

    /**
     * @return array<string>
     */
    private function getVersionSpecificNotes(string $fromVersion, string $toVersion): array
    {
        $notes = [];

        if ($fromVersion === '3.2' && version_compare($toVersion, '5.0', '>=')) {
            $notes[] = "Configuration files need to be restructured";
            $notes[] = "Template syntax has changed significantly";
            $notes[] = "Database configuration format is different";
        }

        if (version_compare($fromVersion, '5.0', '<=') && version_compare($toVersion, '5.1', '>=')) {
            $notes[] = "Configuration files are now split into separate files";
            $notes[] = "All configuration uses dot notation";
            $notes[] = "System constants have been replaced with facade methods";
        }

        if (version_compare($fromVersion, '5.1', '<=') && version_compare($toVersion, '6.0', '>=')) {
            $notes[] = "think\\Controller class has been removed";
            $notes[] = "All facade classes must use full namespaces";
            $notes[] = "Database helper functions have been deprecated";
        }

        if (version_compare($fromVersion, '6.0', '<=') && version_compare($toVersion, '8.0', '>=')) {
            $notes[] = "This is mostly a seamless upgrade";
            $notes[] = "Consider installing think-filesystem if upgrading from 6.0";
        }

        return $notes;
    }

    public function saveReport(string $content, string $filePath): bool
    {
        return file_put_contents($filePath, $content) !== false;
    }

    public function reset(): void
    {
        $this->changes = [];
        $this->warnings = [];
        $this->errors = [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
