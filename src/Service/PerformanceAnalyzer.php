<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

/**
 * Analyzes performance metrics during ThinkPHP upgrades
 */
final class PerformanceAnalyzer
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $metrics = [];

    /**
     * @var array<string, float>
     */
    private array $timers = [];

    /**
     * @var array<string, int>
     */
    private array $counters = [];

    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            throw new \InvalidArgumentException("Timer '{$name}' was not started");
        }

        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);

        $this->recordMetric($name, 'duration', $duration);
        
        return $duration;
    }

    public function incrementCounter(string $name, int $value = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
        $this->recordMetric($name, 'count', $this->counters[$name]);
    }

    public function recordMemoryUsage(string $context): void
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $this->recordMetric($context, 'memory_usage', $memoryUsage);
        $this->recordMetric($context, 'peak_memory', $peakMemory);
    }

    public function recordFileMetrics(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $fileSize = filesize($filePath);
        $lineCount = $this->countLines($filePath);
        
        $this->recordMetric('file_processing', 'file_size', $fileSize);
        $this->recordMetric('file_processing', 'line_count', $lineCount);
        $this->incrementCounter('files_processed');
    }

    public function recordTransformationMetrics(string $rectorRule, int $transformations): void
    {
        $this->recordMetric($rectorRule, 'transformations', $transformations);
        $this->incrementCounter('total_transformations', $transformations);
    }

    public function recordMetric(string $category, string $metric, $value): void
    {
        if (!isset($this->metrics[$category])) {
            $this->metrics[$category] = [];
        }

        if (!isset($this->metrics[$category][$metric])) {
            $this->metrics[$category][$metric] = [];
        }

        $this->metrics[$category][$metric][] = [
            'value' => $value,
            'timestamp' => microtime(true),
        ];
    }

    public function generateReport(): array
    {
        return [
            'summary' => $this->generateSummary(),
            'performance_metrics' => $this->generatePerformanceMetrics(),
            'resource_usage' => $this->generateResourceUsage(),
            'transformation_stats' => $this->generateTransformationStats(),
            'bottlenecks' => $this->identifyBottlenecks(),
            'recommendations' => $this->generateRecommendations(),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function generateSummary(): array
    {
        $totalDuration = $this->getTotalDuration();
        $totalFiles = $this->counters['files_processed'] ?? 0;
        $totalTransformations = $this->counters['total_transformations'] ?? 0;

        return [
            'total_duration' => $totalDuration,
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            'files_processed' => $totalFiles,
            'total_transformations' => $totalTransformations,
            'average_time_per_file' => $totalFiles > 0 ? $totalDuration / $totalFiles : 0,
            'transformations_per_second' => $totalDuration > 0 ? $totalTransformations / $totalDuration : 0,
        ];
    }

    private function generatePerformanceMetrics(): array
    {
        $metrics = [];

        foreach ($this->metrics as $category => $categoryMetrics) {
            if (isset($categoryMetrics['duration'])) {
                $durations = array_column($categoryMetrics['duration'], 'value');
                $metrics[$category] = [
                    'total_duration' => array_sum($durations),
                    'average_duration' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
                    'min_duration' => count($durations) > 0 ? min($durations) : 0,
                    'max_duration' => count($durations) > 0 ? max($durations) : 0,
                    'call_count' => count($durations),
                ];
            }
        }

        return $metrics;
    }

    private function generateResourceUsage(): array
    {
        $memoryMetrics = [];
        
        if (isset($this->metrics['memory_usage'])) {
            foreach ($this->metrics as $category => $categoryMetrics) {
                if (isset($categoryMetrics['memory_usage'])) {
                    $memoryUsages = array_column($categoryMetrics['memory_usage'], 'value');
                    $memoryMetrics[$category] = [
                        'average_memory' => count($memoryUsages) > 0 ? array_sum($memoryUsages) / count($memoryUsages) : 0,
                        'peak_memory' => count($memoryUsages) > 0 ? max($memoryUsages) : 0,
                        'memory_formatted' => $this->formatBytes(count($memoryUsages) > 0 ? max($memoryUsages) : 0),
                    ];
                }
            }
        }

        return [
            'current_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'current_memory_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
            'by_category' => $memoryMetrics,
        ];
    }

    private function generateTransformationStats(): array
    {
        $stats = [];

        foreach ($this->metrics as $category => $categoryMetrics) {
            if (isset($categoryMetrics['transformations'])) {
                $transformations = array_column($categoryMetrics['transformations'], 'value');
                $stats[$category] = [
                    'total_transformations' => array_sum($transformations),
                    'average_per_execution' => count($transformations) > 0 ? array_sum($transformations) / count($transformations) : 0,
                    'executions' => count($transformations),
                ];
            }
        }

        // Sort by total transformations
        uasort($stats, function ($a, $b) {
            return $b['total_transformations'] <=> $a['total_transformations'];
        });

        return $stats;
    }

    private function identifyBottlenecks(): array
    {
        $bottlenecks = [];

        // Identify slow operations
        $performanceMetrics = $this->generatePerformanceMetrics();
        foreach ($performanceMetrics as $category => $metrics) {
            if ($metrics['average_duration'] > 1.0) { // More than 1 second
                $bottlenecks[] = [
                    'type' => 'slow_operation',
                    'category' => $category,
                    'average_duration' => $metrics['average_duration'],
                    'description' => "Operation '{$category}' is taking an average of {$metrics['average_duration']}s",
                ];
            }
        }

        // Identify memory-intensive operations
        $resourceUsage = $this->generateResourceUsage();
        foreach ($resourceUsage['by_category'] as $category => $usage) {
            if ($usage['peak_memory'] > 100 * 1024 * 1024) { // More than 100MB
                $bottlenecks[] = [
                    'type' => 'memory_intensive',
                    'category' => $category,
                    'peak_memory' => $usage['peak_memory'],
                    'description' => "Operation '{$category}' uses up to {$usage['memory_formatted']} of memory",
                ];
            }
        }

        // Identify frequently called operations
        foreach ($performanceMetrics as $category => $metrics) {
            if ($metrics['call_count'] > 1000) {
                $bottlenecks[] = [
                    'type' => 'frequent_calls',
                    'category' => $category,
                    'call_count' => $metrics['call_count'],
                    'description' => "Operation '{$category}' is called {$metrics['call_count']} times",
                ];
            }
        }

        return $bottlenecks;
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];
        $bottlenecks = $this->identifyBottlenecks();

        foreach ($bottlenecks as $bottleneck) {
            switch ($bottleneck['type']) {
                case 'slow_operation':
                    $recommendations[] = [
                        'type' => 'performance',
                        'priority' => 'high',
                        'message' => "Optimize '{$bottleneck['category']}' operation to reduce execution time",
                        'suggestions' => [
                            'Consider caching results',
                            'Optimize algorithms',
                            'Use parallel processing',
                        ],
                    ];
                    break;

                case 'memory_intensive':
                    $recommendations[] = [
                        'type' => 'memory',
                        'priority' => 'medium',
                        'message' => "Reduce memory usage in '{$bottleneck['category']}' operation",
                        'suggestions' => [
                            'Process files in smaller chunks',
                            'Use streaming instead of loading entire files',
                            'Implement garbage collection',
                        ],
                    ];
                    break;

                case 'frequent_calls':
                    $recommendations[] = [
                        'type' => 'optimization',
                        'priority' => 'medium',
                        'message' => "Reduce frequency of '{$bottleneck['category']}' calls",
                        'suggestions' => [
                            'Implement caching',
                            'Batch operations',
                            'Optimize call patterns',
                        ],
                    ];
                    break;
            }
        }

        // General recommendations
        $summary = $this->generateSummary();
        if ($summary['average_time_per_file'] > 0.5) {
            $recommendations[] = [
                'type' => 'general',
                'priority' => 'low',
                'message' => 'Consider optimizing file processing speed',
                'suggestions' => [
                    'Use faster storage (SSD)',
                    'Increase available RAM',
                    'Optimize Rector rules',
                ],
            ];
        }

        return $recommendations;
    }

    private function getTotalDuration(): float
    {
        $totalDuration = 0;

        foreach ($this->metrics as $categoryMetrics) {
            if (isset($categoryMetrics['duration'])) {
                $durations = array_column($categoryMetrics['duration'], 'value');
                $totalDuration += array_sum($durations);
            }
        }

        return $totalDuration;
    }

    private function countLines(string $filePath): int
    {
        $lineCount = 0;
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            while (fgets($handle) !== false) {
                $lineCount++;
            }
            fclose($handle);
        }

        return $lineCount;
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        } elseif ($seconds < 60) {
            return round($seconds, 2) . 's';
        } else {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . round($remainingSeconds, 2) . 's';
        }
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

    public function reset(): void
    {
        $this->metrics = [];
        $this->timers = [];
        $this->counters = [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * @return array<string, int>
     */
    public function getCounters(): array
    {
        return $this->counters;
    }

    public function exportMetrics(string $format = 'json'): string
    {
        $data = $this->generateReport();

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            
            case 'csv':
                return $this->exportToCsv($data);
            
            case 'xml':
                return $this->exportToXml($data);
            
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    private function exportToCsv(array $data): string
    {
        $csv = "Category,Metric,Value\n";
        
        foreach ($data['performance_metrics'] as $category => $metrics) {
            foreach ($metrics as $metric => $value) {
                $csv .= "{$category},{$metric},{$value}\n";
            }
        }

        return $csv;
    }

    private function exportToXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<performance_report/>');
        $this->arrayToXml($data, $xml);
        
        return $xml->asXML();
    }

    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
}
