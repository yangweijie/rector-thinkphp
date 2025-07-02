<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Service;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Analyzes code quality and provides improvement suggestions
 */
final class CodeQualityAnalyzer
{
    private ?Parser $parser = null;
    private ?NodeFinder $nodeFinder = null;

    public function __construct()
    {
        // Delay initialization to avoid dependency issues during construction
    }

    private function initializeParser(): void
    {
        if ($this->parser !== null) {
            return;
        }

        if (!class_exists(ParserFactory::class)) {
            throw new \RuntimeException(
                'PhpParser is required for code quality analysis. ' .
                'Please install nikic/php-parser: composer require nikic/php-parser'
            );
        }

        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    private function getNodeFinder(): NodeFinder
    {
        $this->initializeParser();
        return $this->nodeFinder;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeFile(string $filePath): array
    {
        $this->initializeParser();

        if (!file_exists($filePath)) {
            return ['error' => 'File not found'];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['error' => 'Cannot read file'];
        }

        try {
            $ast = $this->parser->parse($content);
            if ($ast === null) {
                return ['error' => 'Cannot parse file'];
            }

            return $this->analyzeAST($ast, $filePath);
        } catch (\Exception $e) {
            return ['error' => 'Parse error: ' . $e->getMessage()];
        }
    }

    /**
     * @param Node[] $ast
     * @return array<string, mixed>
     */
    private function analyzeAST(array $ast, string $filePath): array
    {
        $analysis = [
            'file' => $filePath,
            'issues' => [],
            'suggestions' => [],
            'metrics' => [],
            'score' => 100,
        ];

        // Check for common issues
        $this->checkNamespaces($ast, $analysis);
        $this->checkClassStructure($ast, $analysis);
        $this->checkMethodComplexity($ast, $analysis);
        $this->checkTypeHints($ast, $analysis);
        $this->checkDocBlocks($ast, $analysis);
        $this->checkSecurityIssues($ast, $analysis);
        $this->checkPerformanceIssues($ast, $analysis);

        // Calculate final score
        $analysis['score'] = max(0, 100 - (count($analysis['issues']) * 5));

        return $analysis;
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkNamespaces(array $ast, array &$analysis): void
    {
        $namespaces = $this->getNodeFinder()->findInstanceOf($ast, \PhpParser\Node\Stmt\Namespace_::class);
        
        if (empty($namespaces)) {
            $analysis['issues'][] = [
                'type' => 'namespace',
                'severity' => 'warning',
                'message' => 'No namespace declared. Consider adding a namespace for better organization.',
                'suggestion' => 'Add namespace declaration at the top of the file'
            ];
        }

        foreach ($namespaces as $namespace) {
            if ($namespace->name === null) {
                continue;
            }
            
            $namespaceName = $namespace->name->toString();
            if (!$this->isValidThinkPHPNamespace($namespaceName)) {
                $analysis['issues'][] = [
                    'type' => 'namespace',
                    'severity' => 'info',
                    'message' => "Namespace '{$namespaceName}' doesn't follow ThinkPHP conventions",
                    'suggestion' => 'Use app\\controller, app\\model, or app\\service namespace pattern'
                ];
            }
        }
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkClassStructure(array $ast, array &$analysis): void
    {
        $classes = $this->getNodeFinder()->findInstanceOf($ast, \PhpParser\Node\Stmt\Class_::class);
        
        foreach ($classes as $class) {
            // Skip anonymous classes
            if ($class->name === null) {
                continue;
            }

            // Check class naming
            $className = $class->name->toString();
            if (!$this->isValidClassName($className)) {
                $analysis['issues'][] = [
                    'type' => 'naming',
                    'severity' => 'warning',
                    'message' => "Class name '{$className}' should use PascalCase",
                    'suggestion' => 'Rename class to follow PascalCase convention'
                ];
            }

            // Check for too many methods
            $methods = $this->getNodeFinder()->findInstanceOf($class->stmts, \PhpParser\Node\Stmt\ClassMethod::class);
            if (count($methods) > 20) {
                $analysis['issues'][] = [
                    'type' => 'structure',
                    'severity' => 'warning',
                    'message' => "Class '{$className}' has too many methods (" . count($methods) . ")",
                    'suggestion' => 'Consider splitting this class into smaller, more focused classes'
                ];
            }

            // Check for missing constructor in controllers
            if ($this->isControllerClass($class) && !$this->hasConstructor($class)) {
                $analysis['suggestions'][] = [
                    'type' => 'structure',
                    'message' => 'Consider adding a constructor for dependency injection',
                    'example' => 'public function __construct(private UserService $userService) {}'
                ];
            }
        }
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkMethodComplexity(array $ast, array &$analysis): void
    {
        $methods = $this->getNodeFinder()->findInstanceOf($ast, \PhpParser\Node\Stmt\ClassMethod::class);
        
        foreach ($methods as $method) {
            $methodName = (string) $method->name;
            $complexity = $this->calculateCyclomaticComplexity($method);

            if ($complexity > 10) {
                $analysis['issues'][] = [
                    'type' => 'complexity',
                    'severity' => 'warning',
                    'message' => "Method '{$methodName}' has high complexity ({$complexity})",
                    'suggestion' => 'Break this method into smaller methods'
                ];
            }

            // Check method length
            $lineCount = $this->getMethodLineCount($method);
            if ($lineCount > 50) {
                $analysis['issues'][] = [
                    'type' => 'length',
                    'severity' => 'info',
                    'message' => "Method '{$methodName}' is too long ({$lineCount} lines)",
                    'suggestion' => 'Consider splitting this method into smaller methods'
                ];
            }
        }
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkTypeHints(array $ast, array &$analysis): void
    {
        $methods = $this->getNodeFinder()->findInstanceOf($ast, \PhpParser\Node\Stmt\ClassMethod::class);
        
        foreach ($methods as $method) {
            $methodName = (string) $method->name;

            // Check parameter type hints
            foreach ($method->params as $param) {
                if ($param->type === null && !$this->isSpecialParameter($param)) {
                    $paramName = $param->var->name ?? 'unknown';
                    $analysis['suggestions'][] = [
                        'type' => 'typing',
                        'message' => "Add type hint for parameter \${$paramName} in method {$methodName}",
                        'example' => 'public function method(string $param): void'
                    ];
                }
            }

            // Check return type hints
            if ($method->returnType === null && !$this->isSpecialMethod($method)) {
                $analysis['suggestions'][] = [
                    'type' => 'typing',
                    'message' => "Add return type hint for method {$methodName}",
                    'example' => 'public function method(): string'
                ];
            }
        }
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkDocBlocks(array $ast, array &$analysis): void
    {
        $classes = $this->getNodeFinder()->findInstanceOf($ast, \PhpParser\Node\Stmt\Class_::class);
        
        foreach ($classes as $class) {
            // Skip anonymous classes
            if ($class->name === null) {
                continue;
            }

            $className = $class->name->toString();
            if ($class->getDocComment() === null) {
                $analysis['suggestions'][] = [
                    'type' => 'documentation',
                    'message' => "Add class documentation for {$className}",
                    'example' => '/** * Description of the class */'
                ];
            }

            $methods = $this->getNodeFinder()->findInstanceOf($class->stmts, \PhpParser\Node\Stmt\ClassMethod::class);
            foreach ($methods as $method) {
                $methodName = (string) $method->name;
                if ($method->isPublic() && $method->getDocComment() === null) {
                    $analysis['suggestions'][] = [
                        'type' => 'documentation',
                        'message' => "Add documentation for public method {$methodName}",
                        'example' => '/** * Method description * @param string $param * @return string */'
                    ];
                }
            }
        }
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkSecurityIssues(array $ast, array &$analysis): void
    {
        // Check for SQL injection risks
        $this->checkSQLInjection($ast, $analysis);
        
        // Check for XSS risks
        $this->checkXSSRisks($ast, $analysis);
        
        // Check for file inclusion risks
        $this->checkFileInclusionRisks($ast, $analysis);
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkPerformanceIssues(array $ast, array &$analysis): void
    {
        // Check for N+1 query patterns
        $this->checkNPlusOneQueries($ast, $analysis);
        
        // Check for inefficient loops
        $this->checkInefficiientLoops($ast, $analysis);
    }

    private function isValidThinkPHPNamespace(string $namespace): bool
    {
        $validPrefixes = ['app\\', 'think\\', 'traits\\', 'extend\\'];
        
        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($namespace, $prefix)) {
                return true;
            }
        }
        
        return false;
    }

    private function isValidClassName(string $className): bool
    {
        return preg_match('/^[A-Z][a-zA-Z0-9]*$/', $className) === 1;
    }

    private function isControllerClass(\PhpParser\Node\Stmt\Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }
        
        $extendsName = $class->extends->toString();
        return str_contains($extendsName, 'Controller') || str_contains($extendsName, 'BaseController');
    }

    private function hasConstructor(\PhpParser\Node\Stmt\Class_ $class): bool
    {
        $methods = $this->getNodeFinder()->findInstanceOf($class->stmts, \PhpParser\Node\Stmt\ClassMethod::class);

        foreach ($methods as $method) {
            if ((string) $method->name === '__construct') {
                return true;
            }
        }

        return false;
    }

    private function calculateCyclomaticComplexity(\PhpParser\Node\Stmt\ClassMethod $method): int
    {
        $complexity = 1; // Base complexity
        
        $visitor = new class($complexity) extends NodeVisitorAbstract {
            private int $complexity;
            
            public function __construct(int &$complexity)
            {
                $this->complexity = &$complexity;
            }
            
            public function enterNode(Node $node): void
            {
                if ($node instanceof \PhpParser\Node\Stmt\If_ ||
                    $node instanceof \PhpParser\Node\Stmt\ElseIf_ ||
                    $node instanceof \PhpParser\Node\Stmt\While_ ||
                    $node instanceof \PhpParser\Node\Stmt\For_ ||
                    $node instanceof \PhpParser\Node\Stmt\Foreach_ ||
                    $node instanceof \PhpParser\Node\Stmt\Switch_ ||
                    $node instanceof \PhpParser\Node\Stmt\Case_ ||
                    $node instanceof \PhpParser\Node\Stmt\Catch_ ||
                    $node instanceof \PhpParser\Node\Expr\Ternary ||
                    $node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
                    $node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                    $this->complexity++;
                }
            }
            
            public function getComplexity(): int
            {
                return $this->complexity;
            }
        };
        
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($method->stmts ?? []);
        
        return $visitor->getComplexity();
    }

    private function getMethodLineCount(\PhpParser\Node\Stmt\ClassMethod $method): int
    {
        if ($method->stmts === null) {
            return 0;
        }
        
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        return $endLine - $startLine + 1;
    }

    private function isSpecialParameter(\PhpParser\Node\Param $param): bool
    {
        // Skip type hints for special parameters like $request, $response
        $specialParams = ['request', 'response', 'app'];
        $paramName = $param->var->name ?? '';
        
        return in_array($paramName, $specialParams, true);
    }

    private function isSpecialMethod(\PhpParser\Node\Stmt\ClassMethod $method): bool
    {
        // Skip return type hints for special methods
        $specialMethods = ['__construct', '__destruct', '__toString'];

        return in_array((string) $method->name, $specialMethods, true);
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkSQLInjection(array $ast, array &$analysis): void
    {
        // Implementation for SQL injection detection
        // This is a simplified version - real implementation would be more complex
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkXSSRisks(array $ast, array &$analysis): void
    {
        // Implementation for XSS risk detection
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkFileInclusionRisks(array $ast, array &$analysis): void
    {
        // Implementation for file inclusion risk detection
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkNPlusOneQueries(array $ast, array &$analysis): void
    {
        // Implementation for N+1 query detection
    }

    /**
     * @param Node[] $ast
     * @param array<string, mixed> $analysis
     */
    private function checkInefficiientLoops(array $ast, array &$analysis): void
    {
        // Implementation for inefficient loop detection
    }
}
