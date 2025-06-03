<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 db() and model() helper functions to ThinkPHP 6.0 facade calls
 *
 * @see \Rector\ThinkPHP\Tests\Rector\FuncCall\ThinkPHP51To60HelperFunctionRectorTest
 */
final class ThinkPHP51To60HelperFunctionRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 db() and model() helper functions to ThinkPHP 6.0 facade calls',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$users = db('user')->select();
$userModel = model('User');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$users = \think\facade\Db::name('user')->select();
$userModel = new \app\model\User();
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
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $functionName = $this->getName($node->name);
        if ($functionName === null) {
            return null;
        }

        if ($functionName === 'db') {
            return $this->refactorDbFunction($node);
        }

        if ($functionName === 'model') {
            return $this->refactorModelFunction($node);
        }

        return null;
    }

    private function refactorDbFunction(FuncCall $node): ?StaticCall
    {
        // Convert db('table') to \think\facade\Db::name('table')
        $staticCall = new StaticCall(
            new Name('\\think\\facade\\Db'),
            new \PhpParser\Node\Identifier('name')
        );

        // Copy arguments from db() to name()
        $staticCall->args = $node->args;

        return $staticCall;
    }

    private function refactorModelFunction(FuncCall $node): ?\PhpParser\Node\Expr\New_
    {
        // Convert model('User') to new \app\model\User()
        if (!isset($node->args[0]) || !$node->args[0]->value instanceof \PhpParser\Node\Scalar\String_) {
            return null;
        }

        $modelName = $node->args[0]->value->value;
        $className = '\\app\\model\\' . $modelName;

        return new \PhpParser\Node\Expr\New_(new Name($className));
    }
}
