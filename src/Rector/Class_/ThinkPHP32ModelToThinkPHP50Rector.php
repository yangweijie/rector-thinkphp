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
 * @see \Rector\ThinkPHP\Tests\Rector\Class_\ThinkPHP32ModelToThinkPHP50RectorTest
 */
final class ThinkPHP32ModelToThinkPHP50Rector extends AbstractRector implements ConfigurableRectorInterface
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
            'Convert ThinkPHP 3.2 models to ThinkPHP 5.0 format',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class UserModel extends Model
{
    protected $tableName = 'user';

    public function getUserList()
    {
        return $this->select();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
namespace app\index\model;

use think\Model;

class User extends Model
{
    protected $table = 'user';

    public function getUserList()
    {
        return $this->select();
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
        if (!$this->isThinkPHP32Model($node)) {
            return null;
        }

        $hasChanges = false;

        // Update class name (remove Model suffix)
        $className = $this->getName($node);
        if ($className !== null && $this->endsWith($className, 'Model')) {
            $newClassName = substr($className, 0, -5); // Remove 'Model'
            $node->name = new Node\Identifier($newClassName);
        }

        // Update extends clause to use Model (will be resolved by use statement)
        if ($node->extends !== null) {
            $parentName = $this->getName($node->extends);
            if ($parentName === 'Model') {
                $node->extends = new Name('Model');
            }
        }

        // Update property names (tableName -> table)
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($this->isName($prop, 'tableName')) {
                        $prop->name = new Node\VarLikeIdentifier('table');
                    }
                }
            }
        }

        // Create namespace
        $namespaceNode = new Namespace_(new Name('app\\index\\model'));

        // Add use statement for think\Model
        $useNode = new Use_([
            new UseUse(new Name('think\\Model'))
        ]);

        $namespaceNode->stmts = [$useNode, $node];

        return $namespaceNode;
    }

    private function isThinkPHP32Model(Class_ $class): bool
    {
        $className = $this->getName($class);
        if ($className === null) {
            return false;
        }

        // Check if it's a model class
        if (!$this->endsWith($className, 'Model')) {
            return false;
        }

        // Check if it extends Model (ThinkPHP 3.x style)
        if ($class->extends === null) {
            return false;
        }

        $parentName = $this->getName($class->extends);
        return $parentName === 'Model';
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
