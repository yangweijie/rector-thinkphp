# Installation Guide

## Requirements

- PHP 7.4 or higher (PHP 8.0+ recommended)
- Composer

## Installation

### Option 1: Install as Project Dependency (Recommended)

```bash
# Navigate to your ThinkPHP project
cd /path/to/your/thinkphp/project

# Install rector-thinkphp
composer require --dev yangweijie/rector-thinkphp

# Run the upgrade wizard
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard .
```

### Option 2: Global Installation

```bash
# Install globally
composer global require yangweijie/rector-thinkphp

# Make sure ~/.composer/vendor/bin is in your PATH
export PATH="$PATH:$HOME/.composer/vendor/bin"

# Run from anywhere
thinkphp-rector thinkphp:upgrade-wizard /path/to/your/project
```

### Option 3: Development Installation

```bash
# Clone the repository
git clone https://github.com/yangweijie/rector-thinkphp.git
cd rector-thinkphp

# Install dependencies
composer install

# Run directly
./bin/thinkphp-rector thinkphp:upgrade-wizard /path/to/your/project
```

## Verification

After installation, verify that the tool is working:

```bash
# Check version
./vendor/bin/thinkphp-rector --version

# List available commands
./vendor/bin/thinkphp-rector list

# Get help for upgrade wizard
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard --help
```

## Troubleshooting

### Common Issues

1. **"Class not found" errors**
   ```bash
   # Make sure all dependencies are installed
   composer install
   
   # Clear composer cache if needed
   composer clear-cache
   composer install
   ```

2. **Permission errors**
   ```bash
   # Make the binary executable
   chmod +x vendor/bin/thinkphp-rector
   ```

3. **Autoload issues**
   ```bash
   # Regenerate autoload files
   composer dump-autoload
   ```

### Getting Help

If you encounter issues:

1. Check the [GitHub Issues](https://github.com/yangweijie/rector-thinkphp/issues)
2. Create a new issue with:
   - Your PHP version (`php --version`)
   - Your Composer version (`composer --version`)
   - The exact error message
   - Steps to reproduce the issue

## Next Steps

Once installed, see the [README.md](README.md) for usage examples and the [examples documentation](docs/examples.md) for detailed usage scenarios.
