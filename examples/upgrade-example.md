# ThinkPHP Upgrade Example

This example demonstrates a complete upgrade from ThinkPHP 3.2 to 8.0.

## Project Structure

### Before (ThinkPHP 3.2)
```
project/
├── Application/
│   ├── Common/
│   │   └── Conf/
│   │       └── config.php
│   ├── Home/
│   │   ├── Controller/
│   │   │   ├── IndexController.class.php
│   │   │   └── UserController.class.php
│   │   ├── Model/
│   │   │   └── UserModel.class.php
│   │   └── View/
│   └── Admin/
├── Public/
│   └── index.php
└── ThinkPHP/
```

### After (ThinkPHP 8.0)
```
project/
├── app/
│   ├── controller/
│   │   ├── Index.php
│   │   └── User.php
│   ├── model/
│   │   └── User.php
│   └── view/
├── config/
│   ├── app.php
│   ├── database.php
│   └── route.php
├── public/
│   └── index.php
├── composer.json
└── rector.php
```

## Step-by-Step Upgrade

### Step 1: Use the Upgrade Wizard

```bash
vendor/bin/rector thinkphp:upgrade-wizard /path/to/project --backup
```

### Step 2: Review Generated Configuration

The wizard creates `rector-thinkphp-upgrade.php`:

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ThinkPHP\Set\ThinkPHPSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Application',
    ])
    ->withSets([
        ThinkPHPSetList::THINKPHP_32_TO_50,
        ThinkPHPSetList::THINKPHP_50_TO_51,
        ThinkPHPSetList::THINKPHP_51_TO_60,
        ThinkPHPSetList::THINKPHP_60_TO_80,
    ]);
```

### Step 3: Run the Upgrade

```bash
# Preview changes
vendor/bin/rector process --config=rector-thinkphp-upgrade.php --dry-run

# Apply changes
vendor/bin/rector process --config=rector-thinkphp-upgrade.php
```

## Code Transformations

### Controller Transformation

**Before (3.2):**
```php
<?php

class IndexController extends Controller
{
    public function index()
    {
        $users = M('User')->select();
        $this->assign('users', $users);
        $this->display();
    }
    
    public function hello()
    {
        echo 'Hello, ThinkPHP!';
    }
}
```

**After (8.0):**
```php
<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Index extends BaseController
{
    public function index()
    {
        $users = Db::name('User')->select();
        $this->assign('users', $users);
        return $this->fetch();
    }
    
    public function hello()
    {
        return 'Hello, ThinkPHP!';
    }
}
```

### Model Transformation

**Before (3.2):**
```php
<?php

class UserModel extends Model
{
    protected $tableName = 'user';
    protected $pk = 'user_id';
    
    public function getUserList()
    {
        return $this->where(['status' => ['=', 1]])->select();
    }
}
```

**After (8.0):**
```php
<?php

namespace app\model;

use think\Model;

class User extends Model
{
    protected $table = 'user';
    protected $pk = 'user_id';
    
    public function getUserList()
    {
        return $this->where([['status', '=', 1]])->select();
    }
}
```

### Configuration Transformation

**Before (3.2):**
```php
<?php
// Application/Common/Conf/config.php
return [
    'DB_TYPE' => 'mysql',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'test',
    'DB_USER' => 'root',
    'DB_PWD' => '',
    'DB_PORT' => 3306,
    'DB_PREFIX' => 'tp_',
];
```

**After (8.0):**
```php
<?php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'hostname' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'hostport' => 3306,
            'prefix' => 'tp_',
        ],
    ],
];
```

## Post-Upgrade Tasks

### 1. Update Composer Dependencies

```json
{
    "require": {
        "topthink/framework": "^8.0",
        "topthink/think-orm": "^3.0",
        "topthink/think-view": "^2.0"
    }
}
```

### 2. Update Entry Point

**Before (3.2):**
```php
<?php
// Public/index.php
define('APP_PATH', './Application/');
require './ThinkPHP/ThinkPHP.php';
```

**After (8.0):**
```php
<?php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

$http = (new \think\App())->http;
$response = $http->run();
$response->send();
$http->end($response);
```

### 3. Update Routes

**Before (3.2):**
```php
// No explicit routing, used URL patterns
```

**After (8.0):**
```php
<?php
// config/route.php
use think\facade\Route;

Route::get('/', 'index/index');
Route::get('/user/:id', 'user/read');
```

## Testing the Upgrade

### 1. Run Unit Tests
```bash
vendor/bin/phpunit
```

### 2. Check for Syntax Errors
```bash
find app -name "*.php" -exec php -l {} \;
```

### 3. Test Application Functionality
- Test all major features
- Check database connections
- Verify template rendering
- Test API endpoints

## Common Issues and Solutions

### Issue 1: Namespace Conflicts
**Problem:** Class not found errors
**Solution:** Update autoloader and check namespace declarations

### Issue 2: Configuration Not Loading
**Problem:** Config values returning null
**Solution:** Update config file structure and access patterns

### Issue 3: Database Connection Errors
**Problem:** Cannot connect to database
**Solution:** Update database configuration format

### Issue 4: Template Not Found
**Problem:** View files not rendering
**Solution:** Update template paths and view configuration

## Performance Improvements

After upgrading to ThinkPHP 8.0, you can expect:

- **Better Performance:** Improved framework core
- **PHP 8+ Features:** Constructor promotion, readonly properties
- **Modern Architecture:** Better dependency injection
- **Enhanced Security:** Updated security practices
- **Better Testing:** Improved testing capabilities

## Next Steps

1. **Optimize Code:** Use modern PHP features
2. **Update Dependencies:** Upgrade third-party packages
3. **Improve Architecture:** Implement design patterns
4. **Add Type Hints:** Use strict typing
5. **Write Tests:** Increase test coverage
6. **Monitor Performance:** Set up monitoring tools
