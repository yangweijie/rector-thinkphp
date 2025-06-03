<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.0 where array format to ThinkPHP 5.1 format
 *
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\ThinkPHP50To51WhereArrayRectorTest
 */
final class ThinkPHP50To51WhereArrayRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 where array format to ThinkPHP 5.1 format',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$users = Db::name('user')->where([
    'name' => ['like', 'think%'],
    'id' => ['>', 0],
    'status' => ['=', 1]
])->select();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$users = Db::name('user')->where([
    ['name', 'like', 'think%'],
    ['id', '>', 0],
    ['status', '=', 1]
])->select();
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
        // Check if this is a where() method call
        if (!$this->isName($node->name, 'where')) {
            return null;
        }

        // Check if the first argument is an array
        if (!isset($node->args[0]) || !$node->args[0]->value instanceof Array_) {
            return null;
        }

        $arrayArg = $node->args[0]->value;
        $hasChanged = false;
        $newItems = [];

        foreach ($arrayArg->items as $item) {
            if (!$item instanceof ArrayItem || $item->key === null) {
                $newItems[] = $item;
                continue;
            }

            // Check if the value is an array with operator and value
            if ($item->value instanceof Array_ && count($item->value->items) >= 2) {
                $valueArray = $item->value;
                $operator = $valueArray->items[0]->value ?? null;
                $value = $valueArray->items[1]->value ?? null;

                if ($operator instanceof String_ && $value !== null) {
                    // Convert ['field' => ['operator', 'value']] to ['field', 'operator', 'value']
                    $newArray = new Array_([
                        new ArrayItem($item->key),
                        new ArrayItem($operator),
                        new ArrayItem($value)
                    ]);
                    $newItems[] = new ArrayItem($newArray);
                    $hasChanged = true;
                } else {
                    $newItems[] = $item;
                }
            } else {
                // Keep simple key-value pairs unchanged
                $newItems[] = $item;
            }
        }

        if ($hasChanged) {
            $arrayArg->items = $newItems;
            return $node;
        }

        return null;
    }
}
