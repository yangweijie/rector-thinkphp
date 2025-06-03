<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Stmt;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert echo statements in controller methods to return statements
 *
 * @see \Rector\ThinkPHP\Tests\Rector\Stmt\ThinkPHP32EchoToReturnRectorTest
 */
final class ThinkPHP32EchoToReturnRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert echo statements in controller methods to return statements',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function hello()
    {
        echo 'hello,thinkphp!';
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function hello()
    {
        return 'hello,thinkphp!';
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
        return [Echo_::class, Expression::class];
    }

    /**
     * @param Echo_|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Echo_) {
            return $this->refactorEcho($node);
        }

        if ($node instanceof Expression) {
            return $this->refactorExpression($node);
        }

        return null;
    }

    private function refactorEcho(Echo_ $node): ?Return_
    {
        // Convert echo to return statement
        // If there are multiple expressions, only convert if there's one
        if (count($node->exprs) === 1) {
            return new Return_($node->exprs[0]);
        }

        return null;
    }

    private function refactorExpression(Expression $node): ?Return_
    {
        // Check if this is a print or printf function call
        if (!$node->expr instanceof FuncCall) {
            return null;
        }

        $funcCall = $node->expr;
        if (!$funcCall->name instanceof Name) {
            return null;
        }

        $functionName = $this->getName($funcCall->name);
        
        // Convert print() function calls to return statements
        if ($functionName === 'print' && isset($funcCall->args[0])) {
            return new Return_($funcCall->args[0]->value);
        }

        return null;
    }
}
