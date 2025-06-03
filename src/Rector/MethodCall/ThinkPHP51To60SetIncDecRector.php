<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 setInc/setDec methods to ThinkPHP 6.0 inc/dec methods
 *
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\ThinkPHP51To60SetIncDecRectorTest
 */
final class ThinkPHP51To60SetIncDecRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 setInc/setDec methods to ThinkPHP 6.0 inc/dec methods',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
Db::name('user')->where('id', 1)->setInc('score', 10);
Db::name('user')->where('id', 1)->setDec('score', 5);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
Db::name('user')->where('id', 1)->inc('score', 10)->update();
Db::name('user')->where('id', 1)->dec('score', 5)->update();
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
        $methodName = null;
        if ($this->isName($node->name, 'setInc')) {
            $methodName = 'inc';
        } elseif ($this->isName($node->name, 'setDec')) {
            $methodName = 'dec';
        }

        if ($methodName === null) {
            return null;
        }

        // Change method name
        $node->name = new Identifier($methodName);

        // Add ->update() call
        $updateCall = new MethodCall($node, new Identifier('update'));

        return $updateCall;
    }
}
