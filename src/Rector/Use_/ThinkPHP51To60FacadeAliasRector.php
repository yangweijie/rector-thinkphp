<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Use_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 facade aliases to ThinkPHP 6.0 full facade namespaces
 *
 * @see \Rector\ThinkPHP\Tests\Rector\Use_\ThinkPHP51To60FacadeAliasRectorTest
 */
final class ThinkPHP51To60FacadeAliasRector extends AbstractRector
{
    /**
     * @var array<string, string> Mapping from aliases to full facade namespaces
     */
    private const ALIAS_MAP = [
        'Route' => 'think\\facade\\Route',
        'Config' => 'think\\facade\\Config',
        'Cache' => 'think\\facade\\Cache',
        'Session' => 'think\\facade\\Session',
        'Request' => 'think\\facade\\Request',
        'Response' => 'think\\facade\\Response',
        'View' => 'think\\facade\\View',
        'Log' => 'think\\facade\\Log',
        'App' => 'think\\facade\\App',
        'Env' => 'think\\facade\\Env',
        'Lang' => 'think\\facade\\Lang',
        'Validate' => 'think\\facade\\Validate',
        'Cookie' => 'think\\facade\\Cookie',
        'Url' => 'think\\facade\\Url',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 facade aliases to ThinkPHP 6.0 full facade namespaces',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Route;
use Config;
use Cache;

class IndexController
{
    public function index()
    {
        Route::get('hello', 'index/hello');
        $config = Config::get('app.debug');
        Cache::set('key', 'value');
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\facade\Route;
use think\facade\Config;
use think\facade\Cache;

class IndexController
{
    public function index()
    {
        Route::get('hello', 'index/hello');
        $config = Config::get('app.debug');
        Cache::set('key', 'value');
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Use_::class];
    }

    /**
     * @param Use_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->uses as $use) {
            if (!$use instanceof UseUse) {
                continue;
            }

            $useName = $this->getName($use->name);
            if ($useName === null) {
                continue;
            }

            // Check if this is one of the facade aliases
            if (isset(self::ALIAS_MAP[$useName])) {
                $fullNamespace = self::ALIAS_MAP[$useName];
                $use->name = new Name($fullNamespace);
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }
}
