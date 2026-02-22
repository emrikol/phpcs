# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-02-22

### Added

- `Emrikol.Comments.DocblockTypeSync` — ensures docblock `@param`/`@return` tags match code-side type declarations. Auto-generates missing docblocks, adds missing tags, fills in empty types, and warns on type drift. Recognizes valid type specializations (e.g., `string[]` for `array`, `\WP_Post` for `object`). Configurable `generate_missing_docblocks` and `report_type_drift` properties.
- `Emrikol.Classes.TypedProperty` — enforces type declarations on all class properties. Skips constructor promotion parameters (handled by `TypeHinting`).
- `Emrikol.PHP.DiscouragedMixedType` — warns when `mixed` is used as a type for parameters, return types, or properties. Uses warnings (not errors) since `mixed` is sometimes necessary. Covers functions, closures, and arrow functions.
- `Emrikol.Comments.PhpcsDirective` — enforces surgical use of PHPCS suppression directives. Flags bare `phpcs:ignore`/`phpcs:disable`, unmatched `phpcs:disable` without `phpcs:enable`, and `phpcs:ignore-file`. Auto-fixes legacy `@codingStandardsIgnore*` directives to modern equivalents. Errors from this sniff cannot be self-suppressed.
- `Emrikol.WordPress.NoHookClosure` — forbids closures and arrow functions as WordPress hook callbacks (`add_action`/`add_filter`). Closures cannot be unhooked, breaking extensibility. Configurable `hook_functions` property for custom wrappers.
- `Emrikol.WordPress.RequirePermissionCallback` — requires `permission_callback` in `register_rest_route()` args. Handles single-route and multi-route formats. Configurable `route_functions` property for custom wrappers.
- `Emrikol.PHP.DisallowSuperglobalWrite` — forbids writing to superglobals (`$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_COOKIE`, `$_SESSION`, `$_FILES`, `$_ENV`). Catches direct assignment, compound assignment, and `unset()`. Configurable `superglobals` property.
- Warning assertion helpers in `BaseSniffTestCase`: `get_warning_line_counts()`, `get_warning_codes_by_line()`, `assert_warning_count_on_line()`, `assert_no_warning_on_line()`, `assert_no_warnings()`, `assert_warning_code_on_line()`.
- Runtime auto-detection of PHP built-in classes via `Emrikol\Utils\PhpBuiltinClasses`. Both `GlobalNamespaceSniff` and `TypeDeclarationSniff` now automatically recognize built-in PHP classes without needing preset files.
- `auto_detect_php_classes` property (default `true`) on `GlobalNamespaceSniff` and `TypeDeclarationSniff`, configurable via `ruleset.xml`.
- `bin/generate-php-class-list.sh` — automated script to regenerate PHP built-in class lists using Docker and the endoflife.date API.
- Per-version presets: `presets/php-8.2-classes.xml`, `php-8.3-classes.xml`, `php-8.4-classes.xml`, `php-8.5-classes.xml`.
- Auto-detection of currently supported PHP versions via endoflife.date API (with jq/python3 fallback).
- Automatic cleanup of stale per-version presets for EOL PHP versions.

### Changed

- `presets/php-global-classes.xml` is now auto-generated from official PHP Docker images (254 classes/interfaces across PHP 8.2–8.5, up from ~75 hand-curated).

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
- Preset: `presets/php-global-classes.xml` — built-in PHP classes and interfaces for namespace checking.
- `bin/generate-wp-class-list.sh` — automated script to regenerate the WordPress class list from the latest nightly build.
- Pre-push git hook to validate CHANGELOG.md entries before pushing version tags.
