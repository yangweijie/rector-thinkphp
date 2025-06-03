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
 * Convert ThinkPHP 5.1 Config::pull() to ThinkPHP 6.0 Config::get()
 *
 * @see \Rector\ThinkPHP\Tests\Rector\StaticCall\ThinkPHP51To60ConfigRectorTest
 */
final class ThinkPHP51To60ConfigRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 Config::pull() to ThinkPHP 6.0 Config::get()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\facade\Config;

$appConfig = Config::pull('app');
$dbConfig = Config::pull('database');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\facade\Config;

$appConfig = Config::get('app');
$dbConfig = Config::get('database');
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
        if ($className !== 'Config' && $className !== 'think\\facade\\Config') {
            return null;
        }

        // Check if this is the pull method
        if (!$this->isName($node->name, 'pull')) {
            return null;
        }

        // Change pull to get
        $node->name = new Identifier('get');

        return $node;
    }
}
