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
 * Convert ThinkPHP 5.1 think\Controller to ThinkPHP 6.0 app\BaseController
 *
 * @see \Rector\ThinkPHP\Tests\Rector\Use_\ThinkPHP51To60ControllerRectorTest
 */
final class ThinkPHP51To60ControllerRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 think\Controller to ThinkPHP 6.0 app\BaseController',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\Controller;

class IndexController extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use app\BaseController;

class IndexController extends BaseController
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
        return [Use_::class, \PhpParser\Node\Stmt\Class_::class];
    }

    /**
     * @param Use_|\PhpParser\Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Use_) {
            return $this->refactorUse($node);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            return $this->refactorClass($node);
        }

        return null;
    }

    private function refactorUse(Use_ $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->uses as $use) {
            if (!$use instanceof UseUse) {
                continue;
            }

            $useName = $this->getName($use->name);
            if ($useName === 'think\\Controller') {
                $use->name = new Name('app\\BaseController');
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }

    private function refactorClass(\PhpParser\Node\Stmt\Class_ $node): ?Node
    {
        if ($node->extends === null) {
            return null;
        }

        $extendsName = $this->getName($node->extends);
        if ($extendsName === 'Controller') {
            $node->extends = new Name('BaseController');
            return $node;
        }

        return null;
    }
}
