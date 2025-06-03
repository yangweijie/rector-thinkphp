<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 3.2 M() database queries to ThinkPHP 5.0 db() queries
 *
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\ThinkPHP32DatabaseQueryRectorTest
 */
final class ThinkPHP32DatabaseQueryRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 M() database queries to ThinkPHP 5.0 db() queries',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$user = M('User')->where(['name' => 'thinkphp'])->find();
$users = M('User')->where(['status' => 1])->select();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$user = db('User')->where('name', 'thinkphp')->find();
$users = db('User')->where('status', 1)->select();
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

        // Check if the var is a function call to M() or db()
        if (!$node->var instanceof FuncCall) {
            return null;
        }

        $funcCall = $node->var;
        if (!$funcCall->name instanceof Name) {
            return null;
        }

        $functionName = $this->getName($funcCall->name);
        
        // Only process M() function calls
        if ($functionName !== 'M') {
            return null;
        }

        // Convert M() to db()
        $funcCall->name = new Name('db');

        // Convert where() arguments from array format to individual arguments
        if (isset($node->args[0]) && $node->args[0]->value instanceof Array_) {
            $arrayArg = $node->args[0]->value;
            
            // Only convert simple key-value arrays
            if (count($arrayArg->items) === 1) {
                $item = $arrayArg->items[0];
                if ($item instanceof ArrayItem && $item->key instanceof String_ && $item->value !== null) {
                    // Convert ['key' => 'value'] to 'key', 'value'
                    $node->args = [
                        new Arg($item->key),
                        new Arg($item->value)
                    ];
                }
            }
        }

        return $node;
    }
}
