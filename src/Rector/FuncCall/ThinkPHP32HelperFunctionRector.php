<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 3.2 single-letter helper functions to ThinkPHP 5.0 helper functions
 *
 * @see \Rector\ThinkPHP\Tests\Rector\FuncCall\ThinkPHP32HelperFunctionRectorTest
 */
final class ThinkPHP32HelperFunctionRector extends AbstractRector
{
    /**
     * @var array<string, string> Mapping from 3.2 functions to 5.0 functions
     */
    private const FUNCTION_MAP = [
        'C' => 'config',
        'M' => 'db',
        'D' => 'model',
        'I' => 'input',
        'L' => 'lang',
        'U' => 'url',
        'S' => 'cache',
        'A' => 'controller',
        'R' => 'action',
        'W' => 'widget',
        'E' => 'exception',
        'G' => 'debug',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 3.2 single-letter helper functions to ThinkPHP 5.0 helper functions',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$config = C('database');
$user = M('User')->find(1);
$model = D('User');
$name = I('get.name');
$lang = L('hello');
$url = U('Index/index');
$cache = S('key');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$config = config('database');
$user = db('User')->find(1);
$model = model('User');
$name = input('get.name');
$lang = lang('hello');
$url = url('Index/index');
$cache = cache('key');
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
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $functionName = $this->getName($node->name);
        if ($functionName === null) {
            return null;
        }

        // Check if this is one of the ThinkPHP 3.2 helper functions
        if (!isset(self::FUNCTION_MAP[$functionName])) {
            return null;
        }

        // Replace with the new function name
        $newFunctionName = self::FUNCTION_MAP[$functionName];
        $node->name = new Name($newFunctionName);

        return $node;
    }
}
