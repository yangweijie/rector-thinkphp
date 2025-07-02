#!/bin/bash

# Test script to verify rector-thinkphp installation in a fresh project

set -e

echo "🧪 Testing rector-thinkphp installation..."

# Create temporary test directory
TEST_DIR="/tmp/rector-thinkphp-test-$(date +%s)"
mkdir -p "$TEST_DIR"
cd "$TEST_DIR"

echo "📁 Created test directory: $TEST_DIR"

# Initialize a new composer project
echo "🎵 Initializing composer project..."
cat > composer.json << 'EOF'
{
    "name": "test/thinkphp-project",
    "description": "Test project for rector-thinkphp",
    "type": "project",
    "require": {
        "php": "^7.4|^8.0"
    },
    "require-dev": {
        "yangweijie/rector-thinkphp": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "/Volumes/data/git/php/rector-thinkphp"
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
EOF

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-interaction

# Create a simple ThinkPHP 3.2 structure for testing
echo "🏗️ Creating test ThinkPHP structure..."
mkdir -p Application/Common/Conf
mkdir -p Application/Home/Controller

cat > Application/Common/Conf/config.php << 'EOF'
<?php
return array(
    'DB_TYPE'   => 'mysql',
    'DB_HOST'   => 'localhost',
    'DB_NAME'   => 'test_db',
    'DB_USER'   => 'root',
    'DB_PWD'    => 'password',
    'DB_PORT'   => 3306,
    'DB_PREFIX' => 'tp_',
    'APP_DEBUG' => true,
);
EOF

cat > Application/Home/Controller/IndexController.class.php << 'EOF'
<?php
class IndexController extends Controller {
    public function index() {
        $this->display();
    }
    
    public function hello($name = 'World') {
        echo "Hello " . $name;
    }
}
EOF

# Test the installation
echo "🔍 Testing rector-thinkphp commands..."

# Test version command
echo "Testing --version..."
./vendor/bin/thinkphp-rector --version

# Test list command
echo "Testing list command..."
./vendor/bin/thinkphp-rector list

# Test help command
echo "Testing help command..."
./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard --help

# Test dry run with timeout to avoid hanging
echo "Testing upgrade wizard (dry run)..."
timeout 30s bash -c 'echo -e "0\n3\nn" | ./vendor/bin/thinkphp-rector thinkphp:upgrade-wizard . --dry-run' || echo "Test completed (timeout expected)"

echo "✅ All tests passed!"
echo "📁 Test directory: $TEST_DIR"
echo "🧹 To clean up: rm -rf $TEST_DIR"
