# Contributing to Rector ThinkPHP

Thank you for your interest in contributing to Rector ThinkPHP! This document provides guidelines for contributing to the project.

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/rector/rector-thinkphp.git
cd rector-thinkphp
```

2. Install dependencies:
```bash
composer install
```

3. Run tests to ensure everything is working:
```bash
composer test
```

## Creating New Rules

### 1. Create the Rector Rule

Create a new rule in the appropriate directory under `src/Rector/`:

```php
<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\YourCategory;

use PhpParser\Node;
use Rector\ThinkPHP\AbstractThinkPHPRector;
use Rector\ThinkPHP\ValueObject\ThinkPHPVersion;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class YourNewRector extends AbstractThinkPHPRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Description of what your rule does',
            [
                new CodeSample(
                    // Before
                    'old code',
                    // After
                    'new code'
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class]; // or other node types
    }

    public function refactor(Node $node): ?Node
    {
        // Your refactoring logic here
        return $node;
    }

    public function getSourceVersion(): string
    {
        return ThinkPHPVersion::VERSION_X_X;
    }

    public function getTargetVersion(): string
    {
        return ThinkPHPVersion::VERSION_Y_Y;
    }
}
```

### 2. Add Tests

Create a test file in `tests/Rector/YourCategory/` using Pest:

```php
<?php

declare(strict_types=1);

use Rector\ThinkPHP\Rector\YourCategory\YourNewRector;

it('transforms your specific case', function () {
    $this->doTestFile(__DIR__ . '/Fixture/your_test_case.php.inc');
});

it('handles edge cases', function () {
    $this->doTestFile(__DIR__ . '/Fixture/edge_case.php.inc');
});

it('skips irrelevant code', function () {
    $this->doTestFile(__DIR__ . '/Fixture/skip_case.php.inc');
});

function provideConfigFilePath(): string
{
    return __DIR__ . '/config/configured_rule.php';
}
```

### 3. Add Test Fixtures

Create test fixtures in `tests/Rector/YourCategory/Fixture/`:

```php
<?php

// Before code
class OldCode
{
    // ...
}

?>
-----
<?php

// After code
class NewCode
{
    // ...
}

?>
```

### 4. Update Configuration

Add your rule to the appropriate set in `config/sets/`:

```php
use Rector\ThinkPHP\Rector\YourCategory\YourNewRector;

return RectorConfig::configure()
    ->withRules([
        YourNewRector::class,
    ]);
```

## Code Style

We follow PSR-12 coding standards. Run the following commands to check and fix code style:

```bash
composer cs-check
composer cs-fix
```

## Static Analysis

We use PHPStan for static analysis:

```bash
composer phpstan
```

## Testing

This project uses [Pest](https://pestphp.com/) for testing.

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run specific test files:

```bash
./vendor/bin/pest tests/Rector/Class_/ThinkPHP32ControllerToThinkPHP50RectorTest.php
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Add tests for your changes
5. Ensure all tests pass: `composer test`
6. Ensure code style is correct: `composer cs-fix`
7. Ensure static analysis passes: `composer phpstan`
8. Commit your changes: `git commit -am 'Add some feature'`
9. Push to the branch: `git push origin feature/your-feature-name`
10. Create a Pull Request

## Commit Message Guidelines

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

## Questions?

If you have questions about contributing, please open an issue or start a discussion.
