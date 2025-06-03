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
 * Convert ThinkPHP 5.0 system class namespaces to ThinkPHP 5.1 facade namespaces
 *
 * @see \Rector\ThinkPHP\Tests\Rector\Use_\ThinkPHP50To51NamespaceRectorTest
 */
final class ThinkPHP50To51NamespaceRector extends AbstractRector
{
    /**
     * @var array<string, string> Mapping from 5.0 namespaces to 5.1 facade namespaces
     */
    private const NAMESPACE_MAP = [
        'think\\App' => 'think\\facade\\App',
        'think\\Cache' => 'think\\facade\\Cache',
        'think\\Config' => 'think\\facade\\Config',
        'think\\Cookie' => 'think\\facade\\Cookie',
        'think\\Debug' => 'think\\facade\\Debug',
        'think\\Env' => 'think\\facade\\Env',
        'think\\Hook' => 'think\\facade\\Hook',
        'think\\Lang' => 'think\\facade\\Lang',
        'think\\Log' => 'think\\facade\\Log',
        'think\\Request' => 'think\\facade\\Request',
        'think\\Response' => 'think\\facade\\Response',
        'think\\Route' => 'think\\facade\\Route',
        'think\\Session' => 'think\\facade\\Session',
        'think\\Url' => 'think\\facade\\Url',
        'think\\Validate' => 'think\\facade\\Validate',
        'think\\View' => 'think\\facade\\View',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 system class namespaces to ThinkPHP 5.1 facade namespaces',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\App;
use think\Cache;
use think\Config;
use think\Route;

class IndexController
{
    public function index()
    {
        $version = App::version();
        $config = Config::get('app_debug');
        Cache::set('key', 'value');
        Route::get('hello', 'index/hello');
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\facade\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Route;

class IndexController
{
    public function index()
    {
        $version = App::version();
        $config = Config::get('app_debug');
        Cache::set('key', 'value');
        Route::get('hello', 'index/hello');
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

            // Check if this is one of the ThinkPHP 5.0 system classes
            if (isset(self::NAMESPACE_MAP[$useName])) {
                $newNamespace = self::NAMESPACE_MAP[$useName];
                $use->name = new Name($newNamespace);
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }
}
