# Rector ThinkPHP

[![Build Status](https://github.com/rector/rector-thinkphp/workflows/CI/badge.svg)](https://github.com/rector/rector-thinkphp/actions)
[![Downloads](https://img.shields.io/packagist/dt/rector/rector-thinkphp.svg)](https://packagist.org/packages/rector/rector-thinkphp)

Rector rules for upgrading ThinkPHP projects from version 3.1.2 to 8.1.

## Features

### 🚀 **Core Transformation Engine**
- **Automated Code Transformation**: Converts ThinkPHP 3.2 code to modern versions (up to 8.0)
- **Namespace Updates**: Automatically updates class namespaces and imports
- **Method Modernization**: Converts deprecated method calls to new syntax
- **Database Query Updates**: Modernizes database query syntax
- **Template Syntax**: Converts template syntax between versions

### 🧙‍♂️ **Interactive Upgrade Wizard**
- **Smart Version Detection**: Automatically detects your current ThinkPHP version
- **Guided Upgrade Process**: Step-by-step interactive upgrade wizard
- **Backup Creation**: Automatic backup before making changes
- **Comprehensive Analysis**: Code quality analysis and improvement suggestions

### 🔧 **Advanced Services**
- **Configuration Migration**: Automatically migrates config files between versions
- **Dependency Management**: Updates composer.json with correct dependencies
- **Template Conversion**: Handles template syntax changes across versions
- **Code Quality Analysis**: Identifies issues and provides improvement suggestions
- **Upgrade Reports**: Generates detailed reports of all changes made

### 📊 **Quality Assurance**
- **Comprehensive Testing**: 49 test cases ensure reliable transformations
- **Dry Run Mode**: Preview changes before applying them
- **Validation**: Validates configurations and dependencies after upgrade
- **Error Handling**: Robust error handling with detailed feedback

## Installation

```bash
composer require --dev yangweijie/rector-thinkphp
```

For detailed installation instructions, see [INSTALLATION.md](INSTALLATION.md).

## Usage

### 🧙‍♂️ **Interactive Upgrade Wizard (Recommended)**

The easiest way to upgrade your ThinkPHP project:

```bash
# Using the standalone tool (recommended)
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/your/project

# Or using rector directly (if properly configured)
vendor/bin/rector thinkphp:upgrade-wizard /path/to/your/project
```

The wizard will:
- Automatically detect your current ThinkPHP version
- Guide you through selecting the target version
- Create backups if requested
- Migrate configurations and dependencies
- Convert template syntax
- Run code transformations
- Generate a comprehensive upgrade report

#### Wizard Options

```bash
# Specify versions explicitly
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --from-version=5.0 --to-version=6.0

# Create backup before upgrading
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --backup

# Preview changes without applying them
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project --dry-run
```

### 📦 **Batch Upgrade Multiple Projects**

For upgrading multiple projects at once:

```bash
./vendor/bin/thinkphp-rector thinkphp:batch-upgrade config/batch-upgrade.json
```

Example batch configuration file:

```json
{
  "projects": {
    "project-alpha": {
      "path": "/var/www/project-alpha",
      "from_version": "3.2",
      "to_version": "6.0",
      "backup": true
    },
    "project-beta": {
      "path": "/var/www/project-beta",
      "from_version": "5.0",
      "to_version": "8.0",
      "backup": false
    }
  }
}
```

### ⚙️ **Manual Configuration**

Create a `rector.php` configuration file in your project root:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withSets([
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_81,
    ]);
```

Then run Rector:

```bash
vendor/bin/rector process src
```

## Available Rule Sets

### ThinkPHP 3.2 → 5.0
- Convert old-style controllers to namespaced controllers
- Remove Controller/Model suffix from class names
- Convert display() to fetch() with return statements
- Update model class names and property names
- Convert helper functions (C(), M(), D(), etc.)
- Convert echo statements to return statements
- Update system constants to facade calls
- Convert to PSR-4 autoloading structure

### ThinkPHP 5.0 → 5.1
- Update system class namespaces to facade namespaces
- Convert configuration format to dot notation
- Replace system constants with facade method calls
- Update controller initialization method (_initialize → initialize)
- Convert route batch registration (Route::rule → Route::rules)
- Update database query where array format

### ThinkPHP 5.1 → 6.0
- Convert Config::pull() to Config::get()
- Update Db class to facade Db
- Replace setInc/setDec with inc/dec methods
- Convert model get/all methods to find/select
- Remove system facade aliases
- Update think\Controller to app\BaseController
- Convert insert method replace parameter
- Remove db() and model() helper functions

### ThinkPHP 6.0 → 8.0
- Seamless upgrade with minimal changes
- Add think-filesystem dependency if needed
- Update to PHP 8.0+ features
- Convert to modern PHP syntax
- Update type declarations
- Convert to constructor property promotion

## Configuration

You can configure individual rules or rule sets:

```php
<?php

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Rector\Class_\ThinkPHP32ControllerToThinkPHP50Rector;

return RectorConfig::configure()
    ->withRules([
        ThinkPHP32ControllerToThinkPHP50Rector::class,
    ]);
```

## Example

Here's a complete example of what this tool can do:

**Before (ThinkPHP 3.2):**
```php
<?php

class UserController extends Controller
{
    public function index()
    {
        $this->display();
    }

    public function profile()
    {
        $this->display('User:profile');
    }
}

class UserModel extends Model
{
    protected $tableName = 'users';

    public function getUserList()
    {
        return $this->select();
    }
}
```

**After (ThinkPHP 5.0):**
```php
<?php

namespace app\index\controller;

use think\Controller;
class User extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function profile()
    {
        return $this->fetch('User/profile');
    }
}

namespace app\index\model;

use think\Model;
class User extends Model
{
    protected $table = 'users';

    public function getUserList()
    {
        return $this->select();
    }
}
```

## Testing

This project uses [Pest](https://pestphp.com/) for testing. Run tests with:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.
