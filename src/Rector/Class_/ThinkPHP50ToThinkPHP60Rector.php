<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\ThinkPHP\Tests\Rector\Class_\ThinkPHP50ToThinkPHP60RectorTest
 */
final class ThinkPHP50ToThinkPHP60Rector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 classes to ThinkPHP 6.0 format with improved type hints',
            [
                new CodeSample(
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
                    ,
                    <<<'CODE_SAMPLE'
namespace app\controller;

use think\Controller;

class Index extends Controller
{
    public function index(): string
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
        return [Class_::class, Namespace_::class];
    }

    /**
     * @param Class_|Namespace_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Namespace_) {
            return $this->refactorNamespace($node);
        }

        if ($node instanceof Class_) {
            return $this->refactorClass($node);
        }

        return null;
    }

    private function refactorNamespace(Namespace_ $namespace): ?Node
    {
        $namespaceName = $this->getName($namespace->name);
        if ($namespaceName !== null && $this->shouldUpdateNamespace($namespaceName)) {
            $newNamespace = $this->convertNamespaceToThinkPHP60($namespaceName);
            $namespace->name = new Name($newNamespace);
            return $namespace;
        }

        return null;
    }

    private function refactorClass(Class_ $class): ?Node
    {
        if (!$this->isThinkPHP50Class($class)) {
            return null;
        }

        $hasChanges = false;

        // Add return type hints only to controller methods, not model methods
        if ($this->isControllerClass($class)) {
            foreach ($class->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod) {
                    if ($stmt->returnType === null && $this->isControllerActionMethod($stmt)) {
                        $stmt->returnType = new Node\Identifier('string');
                        $hasChanges = true;
                    }
                }
            }
        }

        return $hasChanges ? $class : null;
    }

    private function isThinkPHP50Class(Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parentName = $this->getName($class->extends);
        return $parentName === 'think\\Controller' || $parentName === 'think\\Model';
    }

    private function isControllerActionMethod(Node\Stmt\ClassMethod $method): bool
    {
        // Check if method is public and not a magic method
        $methodName = $this->getName($method);
        return $method->isPublic() && $methodName !== null && !$this->startsWith($methodName, '__');
    }

    private function isControllerClass(Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parentName = $this->getName($class->extends);
        return $parentName === 'think\\Controller';
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function shouldUpdateNamespace(string $namespaceName): bool
    {
        // Check if it's a ThinkPHP 5.0 style namespace with module layer
        return preg_match('/^app\\\\[^\\\\]+\\\\(controller|model)$/', $namespaceName) === 1;
    }

    private function convertNamespaceToThinkPHP60(string $namespaceName): string
    {
        // Convert app\module\controller to app\controller
        // Convert app\module\model to app\model
        if (preg_match('/^app\\\\[^\\\\]+\\\\(controller|model)$/', $namespaceName, $matches)) {
            return 'app\\' . $matches[1];
        }

        return $namespaceName;
    }
}
