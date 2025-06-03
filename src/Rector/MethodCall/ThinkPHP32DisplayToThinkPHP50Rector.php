<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\ThinkPHP32DisplayToThinkPHP50RectorTest
 */
final class ThinkPHP32DisplayToThinkPHP50Rector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 display() method calls to ThinkPHP 5.0 fetch() with return',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function index()
    {
        $this->display();
        $this->display('User:list');
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
        return $this->fetch('User/list');
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
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'display')) {
            return null;
        }

        // Check if this is a $this method call (controller context)
        if (!$this->isName($node->var, 'this')) {
            return null;
        }

        // Change method name from display to fetch
        $node->name = new Node\Identifier('fetch');

        // Convert template path format from "Module:template" to "Module/template"
        if (isset($node->args[0]) && $node->args[0]->value instanceof String_) {
            $templatePath = $node->args[0]->value->value;

            // Convert colon notation to slash notation
            if (strpos($templatePath, ':') !== false) {
                $newTemplatePath = str_replace(':', '/', $templatePath);
                $node->args[0]->value = new String_($newTemplatePath);
            }
        }

        return $node;
    }
}
