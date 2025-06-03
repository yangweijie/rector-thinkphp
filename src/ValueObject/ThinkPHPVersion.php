<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\ValueObject;

/**
 * Value object representing ThinkPHP versions
 */
final class ThinkPHPVersion
{
    /**
     * @var string
     */
    public const VERSION_3_1 = '3.1';

    /**
     * @var string
     */
    public const VERSION_3_2 = '3.2';



    /**
     * @var string
     */
    public const VERSION_5_0 = '5.0';

    /**
     * @var string
     */
    public const VERSION_5_1 = '5.1';

    /**
     * @var string
     */
    public const VERSION_6_0 = '6.0';

    /**
     * @var string
     */
    public const VERSION_6_1 = '6.1';

    /**
     * @var string
     */
    public const VERSION_8_0 = '8.0';

    /**
     * @var string
     */
    public const VERSION_8_1 = '8.1';

    /**
     * @var array<string>
     */
    public const ALL_VERSIONS = [
        self::VERSION_3_1,
        self::VERSION_3_2,
        self::VERSION_5_0,
        self::VERSION_5_1,
        self::VERSION_6_0,
        self::VERSION_6_1,
        self::VERSION_8_0,
        self::VERSION_8_1,
    ];

    public function __construct(
        private readonly string $version
    ) {
        if (!in_array($version, self::ALL_VERSIONS, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid ThinkPHP version "%s"', $version));
        }
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isGreaterThan(self $other): bool
    {
        return version_compare($this->version, $other->version, '>');
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return version_compare($this->version, $other->version, '>=');
    }

    public function isLessThan(self $other): bool
    {
        return version_compare($this->version, $other->version, '<');
    }

    public function isLessThanOrEqual(self $other): bool
    {
        return version_compare($this->version, $other->version, '<=');
    }

    public function equals(self $other): bool
    {
        return $this->version === $other->version;
    }

    public function __toString(): string
    {
        return $this->version;
    }
}
