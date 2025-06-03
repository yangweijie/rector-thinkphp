<?php

declare(strict_types=1);

namespace Rector\ThinkPHP;

use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\ThinkPHP\Contract\ThinkPHPVersionAwareInterface;

/**
 * Abstract base class for all ThinkPHP Rector rules
 */
abstract class AbstractThinkPHPRector extends AbstractRector implements ThinkPHPVersionAwareInterface, ConfigurableRectorInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Check if this rule applies to the given ThinkPHP version
     */
    public function isApplicableToVersion(string $version): bool
    {
        return version_compare($version, $this->getSourceVersion(), '>=') 
            && version_compare($version, $this->getTargetVersion(), '<');
    }

    /**
     * Get configuration value by key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigurationValue(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }

    /**
     * Check if a node is a ThinkPHP class based on common patterns
     */
    protected function isThinkPHPClass(Node $node): bool
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return false;
        }

        // Check for common ThinkPHP class patterns
        $className = $this->getName($node);
        if ($className === null) {
            return false;
        }

        // Common ThinkPHP class suffixes
        $thinkphpSuffixes = ['Controller', 'Model', 'Action', 'Behavior', 'Widget'];
        
        foreach ($thinkphpSuffixes as $suffix) {
            if ($this->endsWith($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a node extends a ThinkPHP base class
     */
    protected function extendsThinkPHPClass(Node\Stmt\Class_ $class, array $baseClasses): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parentClassName = $this->getName($class->extends);

        return in_array($parentClassName, $baseClasses, true);
    }

    /**
     * Check if string ends with given suffix (PHP 7.4 compatible)
     */
    protected function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
