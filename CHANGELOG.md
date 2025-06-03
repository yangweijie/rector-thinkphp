# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of Rector ThinkPHP
- Support for upgrading ThinkPHP 3.1.2 to 8.1 (Note: ThinkPHP 4.x was never officially released)
- PHP 7.4+ compatibility (lowered from PHP 8.0+)
- **ThinkPHP 3.2 to 5.0 upgrade rules (FULLY WORKING):**
  - ✅ Convert controllers to ThinkPHP 5.0 format with proper namespacing
  - ✅ Remove Controller suffix from class names (UserController → User)
  - ✅ Convert display() to return fetch() statements
  - ✅ Update template path format from colon to slash notation (User:profile → User/profile)
  - ✅ Add proper namespace (app\index\controller)
  - ✅ Add use statements (use think\Controller)
  - ✅ Convert models to ThinkPHP 5.0 format with proper namespacing
  - ✅ Remove Model suffix from class names (UserModel → User)
  - ✅ Update property names (tableName → table)
  - ✅ Add model namespace (app\index\model)
  - ✅ Add model use statements (use think\Model)
- ThinkPHP 5.0 to 6.0 upgrade rules:
  - Update namespace structure
  - Add return type hints to controller methods
- ThinkPHP 6.0 to 8.1 upgrade rules:
  - Convert to constructor property promotion
  - Add readonly modifiers where appropriate
- Comprehensive test suite using Pest testing framework
- Documentation and examples
- Feature tests for complete upgrade paths
- Unit tests for all major transformation rules

### Changed
- N/A

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- N/A

### Security
- N/A
