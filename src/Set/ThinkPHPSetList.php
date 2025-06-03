<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Set;

use Rector\Set\Contract\SetListInterface;

/**
 * @see \Rector\ThinkPHP\Tests\Set\ThinkPHPSetListTest
 */
final class ThinkPHPSetList implements SetListInterface
{
    /**
     * @var string
     */
    public const THINKPHP_32_TO_50 = __DIR__ . '/../../config/sets/thinkphp-32-to-50.php';

    /**
     * @var string
     */
    public const THINKPHP_50_TO_51 = __DIR__ . '/../../config/sets/thinkphp-50-to-51.php';

    /**
     * @var string
     */
    public const THINKPHP_51_TO_60 = __DIR__ . '/../../config/sets/thinkphp-51-to-60.php';

    /**
     * @var string
     */
    public const THINKPHP_50_TO_60 = __DIR__ . '/../../config/sets/thinkphp-50-to-60.php';

    /**
     * @var string
     */
    public const THINKPHP_60_TO_80 = __DIR__ . '/../../config/sets/thinkphp-60-to-80.php';

    /**
     * @var string
     */
    public const THINKPHP_60_TO_81 = __DIR__ . '/../../config/sets/thinkphp-60-to-81.php';

    /**
     * @var string
     */
    public const THINKPHP_ALL_VERSIONS = __DIR__ . '/../../config/sets/thinkphp-all-versions.php';
}
