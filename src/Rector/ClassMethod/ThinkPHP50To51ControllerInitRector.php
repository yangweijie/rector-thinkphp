<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.0 controller _initialize method to ThinkPHP 5.1 initialize method
 *
 * @see \Rector\ThinkPHP\Tests\Rector\ClassMethod\ThinkPHP50To51ControllerInitRectorTest
 */
final class ThinkPHP50To51ControllerInitRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 controller _initialize method to ThinkPHP 5.1 initialize method',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\Controller;

class IndexController extends Controller
{
    public function _initialize()
    {
        // initialization code
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\Controller;

class IndexController extends Controller
{
    public function initialize()
    {
        // initialization code
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
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        // Check if this is the _initialize method
        if (!$this->isName($node->name, '_initialize')) {
            return null;
        }

        // Check if this is in a controller class (extends Controller)
        $class = $this->findParentClass($node);
        if ($class === null || !$this->isControllerClass($class)) {
            return null;
        }

        // Rename the method to initialize
        $node->name->name = 'initialize';

        return $node;
    }

    private function findParentClass(Node $node): ?\PhpParser\Node\Stmt\Class_
    {
        $parent = $node;
        while ($parent !== null) {
            $parent = $parent->getAttribute('parent');
            if ($parent instanceof \PhpParser\Node\Stmt\Class_) {
                return $parent;
            }
        }
        return null;
    }

    private function isControllerClass(\PhpParser\Node\Stmt\Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $extendsName = $this->getName($class->extends);
        return $extendsName === 'Controller' || $extendsName === 'think\\Controller';
    }
}
