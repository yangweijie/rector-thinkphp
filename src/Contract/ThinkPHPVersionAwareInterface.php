<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Contract;

/**
 * Interface for rules that are aware of ThinkPHP versions
 */
interface ThinkPHPVersionAwareInterface
{
    /**
     * Get the source ThinkPHP version this rule applies to
     */
    public function getSourceVersion(): string;

    /**
     * Get the target ThinkPHP version this rule upgrades to
     */
    public function getTargetVersion(): string;

    /**
     * Check if this rule applies to the given ThinkPHP version
     */
    public function isApplicableToVersion(string $version): bool;
}
