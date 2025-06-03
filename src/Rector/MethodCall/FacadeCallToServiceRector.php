<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Converts ThinkPHP 5.0 facade calls to dependency injection in ThinkPHP 6.0+
 *
 * @see \Rector\ThinkPHP\Tests\Rector\MethodCall\FacadeCallToServiceRectorTest
 */
final class FacadeCallToServiceRector extends AbstractRector
{
    /**
     * @var array<string, string> Facade to service class mapping
     */
    private const FACADE_TO_SERVICE_MAP = [
        'think\\facade\\Db' => 'think\\db\\Connection',
        'think\\facade\\Cache' => 'think\\cache\\Manager',
        'think\\facade\\Log' => 'think\\log\\Manager',
        'think\\facade\\Request' => 'think\\Request',
        'think\\facade\\Session' => 'think\\session\\Manager',
        'think\\facade\\Config' => 'think\\Config',
        'think\\facade\\View' => 'think\\View',
        'Db' => 'think\\db\\Connection',
        'Cache' => 'think\\cache\\Manager',
        'Log' => 'think\\log\\Manager',
        'Request' => 'think\\Request',
        'Session' => 'think\\session\\Manager',
        'Config' => 'think\\Config',
        'View' => 'think\\View',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 facade calls to dependency injection',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use think\facade\Db;

class UserController extends Controller
{
    public function index()
    {
        $users = Db::table('users')->select();
        return $this->fetch();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use think\db\Connection;

class UserController extends Controller
{
    public function __construct(private Connection $db)
    {
    }

    public function index()
    {
        $users = $this->db->table('users')->select();
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
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->refactorStaticCall($node);
    }

    private function refactorStaticCall(StaticCall $node): ?Node
    {
        if (!$this->isThinkPHPFacadeCall($node)) {
            return null;
        }

        $facadeName = $this->getName($node->class);
        if ($facadeName === null) {
            return null;
        }

        // Convert static call to instance method call
        $serviceProperty = $this->getServicePropertyName($facadeName);
        $methodCall = new MethodCall(
            new PropertyFetch(new Variable('this'), new Identifier($serviceProperty)),
            $node->name,
            $node->args
        );

        return $methodCall;
    }

    private function isThinkPHPFacadeCall(StaticCall $staticCall): bool
    {
        $className = $this->getName($staticCall->class);
        if ($className === null) {
            return false;
        }

        return array_key_exists($className, self::FACADE_TO_SERVICE_MAP);
    }

    private function getServicePropertyName(string $facadeName): string
    {
        // Convert facade name to property name (e.g., 'Db' -> 'db', 'Cache' -> 'cache')
        $baseName = basename(str_replace('\\', '/', $facadeName));
        return lcfirst($baseName);
    }
}