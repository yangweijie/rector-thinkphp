<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\ConstFetch;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 3.2 system constants to ThinkPHP 5.0 Request methods
 *
 * @see \Rector\ThinkPHP\Tests\Rector\ConstFetch\ThinkPHP32SystemConstantRectorTest
 */
final class ThinkPHP32SystemConstantRector extends AbstractRector
{
    /**
     * @var array<string, array{method: string, property?: string}> Mapping from constants to Request methods
     */
    private const CONSTANT_MAP = [
        'REQUEST_METHOD' => ['method' => 'method'],
        'IS_GET' => ['method' => 'isGet'],
        'IS_POST' => ['method' => 'isPost'],
        'IS_PUT' => ['method' => 'isPut'],
        'IS_DELETE' => ['method' => 'isDelete'],
        'IS_AJAX' => ['method' => 'isAjax'],
        'MODULE_NAME' => ['method' => 'module'],
        'CONTROLLER_NAME' => ['method' => 'controller'],
        'ACTION_NAME' => ['method' => 'action'],
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 system constants to ThinkPHP 5.0 Request methods',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
if (REQUEST_METHOD === 'POST') {
    // handle POST request
}

if (IS_AJAX) {
    // handle AJAX request
}

$module = MODULE_NAME;
$controller = CONTROLLER_NAME;
$action = ACTION_NAME;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
if (request()->method() === 'POST') {
    // handle POST request
}

if (request()->isAjax()) {
    // handle AJAX request
}

$module = request()->module();
$controller = request()->controller();
$action = request()->action();
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

        // Check if this is one of the ThinkPHP 3.2 system constants
        if (!isset(self::CONSTANT_MAP[$constantName])) {
            return null;
        }

        $mapping = self::CONSTANT_MAP[$constantName];
        $methodName = $mapping['method'];

        // Create request()->method() call
        $requestCall = new MethodCall(
            new Variable('request'),
            new Identifier($methodName)
        );

        // For some constants, we need to call request() function first
        $requestFuncCall = new MethodCall(
            new \PhpParser\Node\Expr\FuncCall(new Name('request')),
            new Identifier($methodName)
        );

        return $requestFuncCall;
    }
}
