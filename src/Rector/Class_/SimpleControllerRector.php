<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Simple controller rector for testing
 */
final class SimpleControllerRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Simple controller conversion test',
            [
                new CodeSample(
                    'class IndexController extends Controller {}',
                    'namespace app\index\controller; use think\Controller; class Index extends Controller {}'
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $className = $this->getName($node);
        
        // Only process Controller classes
        if ($className === null || !$this->endsWith($className, 'Controller')) {
            return null;
        }

        // Check if extends Controller
        if ($node->extends === null || $this->getName($node->extends) !== 'Controller') {
            return null;
        }

        // Remove Controller suffix
        $newClassName = substr($className, 0, -10);
        $node->name = new Node\Identifier($newClassName);

        // Create namespace
        $namespaceNode = new Namespace_(new Name('app\\index\\controller'));
        
        // Add use statement
        $useNode = new Use_([
            new UseUse(new Name('think\\Controller'))
        ]);
        
        $namespaceNode->stmts = [$useNode, $node];
        
        return $namespaceNode;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
