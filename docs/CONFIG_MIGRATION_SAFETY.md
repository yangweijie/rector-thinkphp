# Configuration Migration Safety

## Overview

The ThinkPHP Rector tool includes a safe configuration migration system that can handle ThinkPHP config files containing Facade calls and other framework-specific code without causing fatal errors.

## Problem

When migrating ThinkPHP projects, configuration files often contain code like:

```php
<?php
use think\facade\Env;

return [
    'app_name' => Env::get('app.name', 'MyApp'),
    'debug' => Env::get('app.debug', false),
    // ... more config
];
```

Directly including these files in the migration tool would cause fatal errors because the ThinkPHP framework classes are not available in the migration environment.

## Solution

The `ConfigMigrator` service uses a safe loading mechanism:

### 1. Safe Config File Loading

```php
private function safeLoadConfigFile(string $filePath): ?array
{
    // Check for problematic code patterns
    if ($this->containsProblematicCode($content)) {
        // Use regex parsing instead of include
        return $this->parseConfigWithRegex($content);
    }
    
    // Try safe include with error handling
    try {
        $config = include $filePath;
        return is_array($config) ? $config : null;
    } catch (\Throwable $e) {
        // Fall back to regex parsing
        return $this->parseConfigWithRegex($content);
    }
}
```

### 2. Problematic Code Detection

The system detects potentially problematic patterns:

- `think\facade\*` - ThinkPHP Facade classes
- `Env::` - Environment facade calls
- `Config::` - Config facade calls
- `use think\` - ThinkPHP namespace imports

### 3. Fallback Mechanisms

When problematic code is detected:

1. **Regex Parsing**: Attempts to extract config arrays using regular expressions
2. **Default Structure**: Provides sensible defaults when parsing fails
3. **Warning Messages**: Informs users about files that couldn't be fully parsed

## Usage

The safe loading is automatically used in all migration operations:

```bash
# This will safely handle config files with Facade calls
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/project
```

## Limitations

- Complex config files with dynamic logic may not be fully parsed
- Facade calls will be replaced with default values
- Custom helper functions in config files are not supported

## Best Practices

1. **Review Generated Configs**: Always review migrated configuration files
2. **Manual Adjustments**: Update environment-specific values manually
3. **Test Thoroughly**: Test your application after migration
4. **Backup First**: Always create backups before migration

## Error Handling

If a config file cannot be parsed:

- A warning is logged
- Default configuration structure is used
- Migration continues with other files
- User is notified to review the file manually

## Example Output

```
Migrating configuration files...
✅ Configuration migration completed
• Migrated: config.php
• Migrated: database.php

⚠️  Warning: Could not safely parse config file: /path/to/complex-config.php
```

This ensures that the migration process is robust and doesn't fail due to framework-specific code in configuration files.
