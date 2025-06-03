<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.0 Route::rule batch registration to ThinkPHP 5.1 Route::rules
 *
 * @see \Rector\ThinkPHP\Tests\Rector\StaticCall\ThinkPHP50To51RouteRectorTest
 */
final class ThinkPHP50To51RouteRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 Route::rule batch registration to ThinkPHP 5.1 Route::rules',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
Route::rule([
    'hello/:name' => 'index/hello',
    'user/:id' => 'user/read'
]);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
Route::rules([
    'hello/:name' => 'index/hello',
    'user/:id' => 'user/read'
]);
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
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->class instanceof Name) {
            return null;
        }

        $className = $this->getName($node->class);
        if ($className !== 'Route' && $className !== 'think\\facade\\Route') {
            return null;
        }

        // Check if this is the rule method with array parameter (batch registration)
        if (!$this->isName($node->name, 'rule')) {
            return null;
        }

        // Check if first argument is an array (indicating batch registration)
        if (!isset($node->args[0]) || !$node->args[0]->value instanceof \PhpParser\Node\Expr\Array_) {
            return null;
        }

        // Change rule to rules for batch registration
        $node->name = new Identifier('rules');

        return $node;
    }
}
