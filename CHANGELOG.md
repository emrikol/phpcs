# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-02-21

### Added

- Initial release consolidating custom PHPCS sniffs from multiple projects.
- `Emrikol.Functions.TypeHinting` — enforces type hints on all function parameters, with phpcbf auto-fix from `@param` docblocks.
- `Emrikol.Functions.TypeDeclaration` — enforces return type declarations on all functions, with phpcbf auto-fix from `@return` docblocks. Supports optional `validate_types` mode with configurable `known_classes`.
- `Emrikol.Namespaces.GlobalNamespace` — ensures global classes in namespaced code are properly qualified. Supports explicit class lists, regex patterns, and presets. Auto-fixes by prepending `\`.
- `Emrikol.Namespaces.Namespace` — enforces namespace declarations on all PHP files.
- `Emrikol.PHP.StrictTypes` — enforces `declare(strict_types=1);` on all PHP files, with phpcbf auto-fix.
- Preset: `presets/wordpress-classes.xml` — 317 WordPress classes for return type validation (from WordPress 7.0-beta1).
- Preset: `presets/wordpress-global-classes.xml` — `/^WP_/` regex pattern for global namespace checking.
- Preset: `presets/php-global-classes.xml` — ~75 common global PHP classes for namespace checking.
- `bin/generate-wp-class-list.sh` — automated script to regenerate the WordPress class list from the latest nightly build.
- Pre-push git hook to validate CHANGELOG.md entries before pushing version tags.
