<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.1 model get/all methods to ThinkPHP 6.0 find/select methods
 *
 * @see \Rector\ThinkPHP\Tests\Rector\StaticCall\ThinkPHP51To60ModelMethodRectorTest
 */
final class ThinkPHP51To60ModelMethodRector extends AbstractRector
{
    /**
     * @var array<string, string> Method mapping
     */
    private const METHOD_MAP = [
        'get' => 'find',
        'all' => 'select',
        'getOrFail' => 'findOrFail',
        'allOrFail' => 'selectOrFail',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.1 model get/all methods to ThinkPHP 6.0 find/select methods',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$user = User::get(1);
$users = User::all();
$user = User::getOrFail(1);
$users = User::allOrFail();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$user = User::find(1);
$users = User::select();
$user = User::findOrFail(1);
$users = User::selectOrFail();
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
        $methodName = $this->getName($node->name);
        if ($methodName === null) {
            return null;
        }

        // Check if this method needs to be converted
        if (!isset(self::METHOD_MAP[$methodName])) {
            return null;
        }

        // Check if this is likely a model class call
        if (!$this->isModelClass($node)) {
            return null;
        }

        // Replace the method name
        $newMethodName = self::METHOD_MAP[$methodName];
        $node->name = new Identifier($newMethodName);

        return $node;
    }

    private function isModelClass(StaticCall $node): bool
    {
        // This is a simple heuristic - in a real implementation,
        // you might want to check if the class extends Model
        $className = $this->getName($node->class);
        
        // Skip if it's a facade or system class
        if ($className === null || 
            str_contains($className, 'facade') || 
            str_contains($className, 'think\\')) {
            return false;
        }

        return true;
    }
}
