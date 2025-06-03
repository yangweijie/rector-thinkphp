<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Use_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 Db class to ThinkPHP 6.0 facade Db
 *
 * @see \Rector\ThinkPHP\Tests\Rector\Use_\ThinkPHP51To60DbFacadeRectorTest
 */
final class ThinkPHP51To60DbFacadeRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 Db class to ThinkPHP 6.0 facade Db',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\Db;

class UserController
{
    public function index()
    {
        $users = Db::name('user')->select();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\facade\Db;

class UserController
{
    public function index()
    {
        $users = Db::name('user')->select();
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
        return [Use_::class];
    }

    /**
     * @param Use_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->uses as $use) {
            if (!$use instanceof UseUse) {
                continue;
            }

            $useName = $this->getName($use->name);
            if ($useName === null) {
                continue;
            }

            // Convert think\Db to think\facade\Db
            if ($useName === 'think\\Db') {
                $use->name = new Name('think\\facade\\Db');
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }
}
