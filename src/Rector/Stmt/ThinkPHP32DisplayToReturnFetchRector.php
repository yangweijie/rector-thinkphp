<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Stmt;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert display() expression statements to return fetch() statements
 */
final class ThinkPHP32DisplayToReturnFetchRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 display() expression statements to return fetch() statements',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function index()
    {
        return $this->fetch();
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
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        // Check if this is a method call expression
        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $node->expr;

        // Check if this is a display() call
        if (!$this->isName($methodCall->name, 'display')) {
            return null;
        }

        // Check if this is a $this method call (controller context)
        if (!$this->isName($methodCall->var, 'this')) {
            return null;
        }

        // Change method name from display to fetch
        $methodCall->name = new Node\Identifier('fetch');

        // Convert template path format from "Module:template" to "Module/template"
        if (isset($methodCall->args[0]) && $methodCall->args[0]->value instanceof Node\Scalar\String_) {
            $templatePath = $methodCall->args[0]->value->value;
            
            // Convert colon notation to slash notation
            if (strpos($templatePath, ':') !== false) {
                $newTemplatePath = str_replace(':', '/', $templatePath);
                $methodCall->args[0]->value = new Node\Scalar\String_($newTemplatePath);
            }
        }

        // Convert expression statement to return statement
        return new Return_($methodCall);
    }
}
