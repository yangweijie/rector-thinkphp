<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\ThinkPHP\Tests\Rector\Class_\ThinkPHP32ControllerToThinkPHP50RectorTest
 */
final class ThinkPHP32ControllerToThinkPHP50Rector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 controllers to ThinkPHP 5.0 format',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
namespace app\index\controller;

use think\Controller;

class Index extends Controller
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isThinkPHP32Controller($node)) {
            return null;
        }

        $hasChanges = false;

        // Update class name (remove Controller suffix)
        $className = $this->getName($node);
        if ($className !== null && $this->endsWith($className, 'Controller')) {
            $newClassName = substr($className, 0, -10); // Remove 'Controller'
            $node->name = new Node\Identifier($newClassName);
            $hasChanges = true;
        }

        // Update extends clause to use think\Controller
        if ($node->extends !== null) {
            $parentName = $this->getName($node->extends);
            if ($parentName === 'Controller') {
                $node->extends = new Name('Controller');
                $hasChanges = true;
            }
        }

        // Create namespace
        $namespaceNode = new Namespace_(new Name('app\\index\\controller'));

        // Add use statement for think\Controller
        $useNode = new Use_([
            new UseUse(new Name('think\\Controller'))
        ]);

        $namespaceNode->stmts = [$useNode, $node];

        return $namespaceNode;
    }

    private function isThinkPHP32Controller(Class_ $class): bool
    {
        $className = $this->getName($class);
        if ($className === null) {
            return false;
        }

        // Check if it's a controller class
        if (!$this->endsWith($className, 'Controller')) {
            return false;
        }

        // Check if it extends Controller (ThinkPHP 3.x style)
        if ($class->extends === null) {
            return false;
        }

        $parentName = $this->getName($class->extends);
        return $parentName === 'Controller' || $parentName === 'Action';
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    private function getConfigurationValue(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }
}
