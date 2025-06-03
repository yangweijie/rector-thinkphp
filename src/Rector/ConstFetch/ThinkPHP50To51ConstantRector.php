<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\ConstFetch;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.0 constants to ThinkPHP 5.1 facade method calls
 *
 * @see \Rector\ThinkPHP\Tests\Rector\ConstFetch\ThinkPHP50To51ConstantRectorTest
 */
final class ThinkPHP50To51ConstantRector extends AbstractRector
{
    /**
     * @var array<string, array{class: string, method: string, args?: array}> Mapping from constants to facade calls
     */
    private const CONSTANT_MAP = [
        'THINK_START_TIME' => ['class' => 'App', 'method' => 'getBeginTime'],
        'THINK_START_MEM' => ['class' => 'App', 'method' => 'getBeginMem'],
        'THINK_VERSION' => ['class' => 'App', 'method' => 'version'],
        'THINK_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['think_path']],
        'APP_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['app_path']],
        'CONFIG_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['config_path']],
        'CONFIG_EXT' => ['class' => 'App', 'method' => 'getConfigExt'],
        'ROOT_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['root_path']],
        'RUNTIME_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['runtime_path']],
        'MODULE_PATH' => ['class' => 'Env', 'method' => 'get', 'args' => ['module_path']],
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 constants to ThinkPHP 5.1 facade method calls',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$version = THINK_VERSION;
$startTime = THINK_START_TIME;
$appPath = APP_PATH;
$rootPath = ROOT_PATH;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$version = App::version();
$startTime = App::getBeginTime();
$appPath = Env::get('app_path');
$rootPath = Env::get('root_path');
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
        return [ConstFetch::class];
    }

    /**
     * @param ConstFetch $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $constantName = $this->getName($node->name);
        if ($constantName === null) {
            return null;
        }

        // Check if this is one of the ThinkPHP 5.0 constants
        if (!isset(self::CONSTANT_MAP[$constantName])) {
            return null;
        }

        $mapping = self::CONSTANT_MAP[$constantName];
        $className = $mapping['class'];
        $methodName = $mapping['method'];

        // Create the static method call
        $staticCall = new StaticCall(
            new Name($className),
            new Identifier($methodName)
        );

        // Add arguments if specified
        if (isset($mapping['args'])) {
            foreach ($mapping['args'] as $arg) {
                $staticCall->args[] = new \PhpParser\Node\Arg(new String_($arg));
            }
        }

        return $staticCall;
    }
}
