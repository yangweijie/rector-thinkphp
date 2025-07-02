# Rector ThinkPHP Examples

This document provides practical examples of how to use Rector ThinkPHP to upgrade your ThinkPHP projects.

## Interactive Upgrade Wizard

The easiest way to upgrade your ThinkPHP project is using the interactive wizard:

```bash
# Using standalone tool (recommended)
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/your/project

# Or using rector directly (if configured)
vendor/bin/rector thinkphp:upgrade-wizard /path/to/your/project
```

The wizard will:
- Automatically detect your current ThinkPHP version
- Guide you through selecting the target version
- Generate appropriate Rector configuration
- Create backups if requested
- Provide step-by-step upgrade instructions

### Wizard Options

```bash
# Specify source and target versions
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --from-version=5.0 --to-version=6.0

# Create backup before upgrading
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --backup

# Dry run to see what would change
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --dry-run
```

## Batch Upgrade

For upgrading multiple projects at once:

```bash
./vendor/bin/thinkphp-rector thinkphp:batch-upgrade examples/batch-upgrade-config.json

# With options
./vendor/bin/thinkphp-rector thinkphp:batch-upgrade config.json --dry-run --backup --parallel=3
```

## Manual Configuration

### Upgrading from ThinkPHP 3.2 to 5.0

Create a `rector.php` configuration file:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/application',
    ])
    ->withSets([
        ThinkPHPSetList::THINKPHP_32_TO_50,
    ]);
```

Run Rector:

```bash
vendor/bin/rector process application
```

**Before (ThinkPHP 3.2):**
```php
<?php

class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }

    public function userList()
    {
        $this->display('User:list');
    }
}
```

**After (ThinkPHP 5.0):**
```php
<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function userList()
    {
        return $this->fetch('User/list');
    }
}
```

### Complete Upgrade (3.2 to 8.1)

For a complete upgrade across all versions:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/application',
    ])
    ->withSets([
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ]);
```

## Advanced Configuration

### Custom Namespaces

You can customize the target namespaces:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ControllerToThinkPHP50Rector;

return RectorConfig::configure()
    ->withConfiguredRule(ThinkPHP32ControllerToThinkPHP50Rector::class, [
        'namespace' => 'App\\Controller',
        'model_namespace' => 'App\\Model',
    ]);
```

### Selective Rules

Apply only specific rules:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ControllerToThinkPHP50Rector;
use Rector\ThinkPHP\Rector\MethodCall\ThinkPHP32DisplayToThinkPHP50Rector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32ControllerToThinkPHP50Rector::class,
        ThinkPHP32DisplayToThinkPHP50Rector::class,
    ]);
```

### Excluding Files

Skip certain files or directories:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/application',
    ])
    ->withSets([
        ThinkPHPSetList::THINKPHP_ALL_VERSIONS,
    ])
    ->withSkip([
        __DIR__ . '/application/vendor',
        __DIR__ . '/application/runtime',
        '*/legacy/*',
    ]);
```

## Model Transformations

### ThinkPHP 3.2 to 5.0 Model

**Before:**
```php
<?php

class UserModel extends Model
{
    protected $tableName = 'user';

    public function getUserList()
    {
        return $this->select();
    }
}
```

**After:**
```php
<?php

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
```

## PHP 8+ Features (6.0 to 8.1)

### Constructor Property Promotion

**Before (ThinkPHP 6.0):**
```php
<?php

namespace app\controller;

use think\Controller;

class Index extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}
```

**After (ThinkPHP 8.1):**
```php
<?php

namespace app\controller;

use think\Controller;

class Index extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }
}
```

## Tips and Best Practices

1. **Backup your code** before running Rector
2. **Run tests** after each upgrade step
3. **Review changes** carefully before committing
4. **Upgrade incrementally** (one major version at a time)
5. **Use version control** to track changes
6. **Test thoroughly** in a development environment first

## Troubleshooting

### Common Issues

1. **Namespace conflicts**: Ensure your autoloader is updated
2. **Missing dependencies**: Update composer.json for new ThinkPHP version
3. **Configuration changes**: Update config files manually if needed
4. **Template paths**: Update template references in views

### Getting Help

- Check the [GitHub Issues](https://github.com/rector/rector-thinkphp/issues)
- Read the [Contributing Guide](CONTRIBUTING.md)
- Review the [Rector Documentation](https://getrector.org/documentation)
