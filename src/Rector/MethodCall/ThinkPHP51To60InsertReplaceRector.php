<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 insert/insertGetId/insertAll replace parameter to ThinkPHP 6.0 replace() method
 *
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\ThinkPHP51To60InsertReplaceRectorTest
 */
final class ThinkPHP51To60InsertReplaceRector extends AbstractRector
{
    /**
     * @var array<string> Methods that support replace parameter
     */
    private const INSERT_METHODS = ['insert', 'insertGetId', 'insertAll'];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 insert/insertGetId/insertAll replace parameter to ThinkPHP 6.0 replace() method',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$data = ['foo' => 'bar', 'bar' => 'foo'];
Db::name('user')->insert($data, true);
Db::name('user')->insertGetId($data, true);
Db::name('user')->insertAll($data, true);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$data = ['foo' => 'bar', 'bar' => 'foo'];
Db::name('user')->replace()->insert($data);
Db::name('user')->replace()->insertGetId($data);
Db::name('user')->replace()->insertAll($data);
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
        $methodName = $this->getName($node->name);
        if ($methodName === null || !in_array($methodName, self::INSERT_METHODS, true)) {
            return null;
        }

        // Check if there's a second parameter (replace parameter)
        if (!isset($node->args[1])) {
            return null;
        }

        $replaceArg = $node->args[1]->value;
        
        // Check if the replace parameter is true
        $isReplace = false;
        if ($replaceArg instanceof ConstFetch && $this->getName($replaceArg->name) === 'true') {
            $isReplace = true;
        } elseif ($replaceArg instanceof LNumber && $replaceArg->value === 1) {
            $isReplace = true;
        }

        if (!$isReplace) {
            return null;
        }

        // Remove the second parameter
        unset($node->args[1]);
        $node->args = array_values($node->args);

        // Add replace() method call before the insert method
        $replaceCall = new MethodCall($node->var, new Identifier('replace'));
        $node->var = $replaceCall;

        return $node;
    }
}
