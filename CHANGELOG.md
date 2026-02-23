# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.3] - 2026-02-23

### Fixed

- `Emrikol.PHP.StrictTypes` — files with `phpcs:disable` (or other PHPCS directives) inside the file-level docblock no longer cause a phpcbf infinite loop (FAILED TO FIX). The sniff's `findNext()` skip list now includes `T_PHPCS_DISABLE`, `T_PHPCS_ENABLE`, `T_PHPCS_IGNORE`, `T_PHPCS_IGNORE_FILE`, and `T_PHPCS_SET`, which the PHPCS tokenizer injects into doc comments containing directives. Previously these tokens caused the sniff to stop scanning before reaching the `T_DECLARE` token, so it never detected the existing `declare(strict_types=1)` and kept inserting duplicates on every fixer pass.

## [0.3.2] - 2026-02-23

### Fixed

- `Emrikol.Comments.DocblockTypeSync` — phpcs directive comments (`// phpcs:ignore`, `// phpcs:disable`, `// phpcs:enable`) between a docblock and its function declaration no longer break the docblock association. Previously, any phpcs directive in that gap caused false `MissingDocblock` errors and the fixer would generate duplicate docblocks. PHPCS tokenizes these directives as `T_PHPCS_IGNORE`/`T_PHPCS_DISABLE`/`T_PHPCS_ENABLE` (not `T_COMMENT`), so they are now included in the `$allowed_between` token list and handled correctly by the fixer's insertion-point logic.

## [0.3.1] - 2026-02-22

### Fixed

- `Emrikol.Namespaces.GlobalNamespace` — constants matching `class_patterns` (e.g. `WP_DEBUG`, `WP_CLI` matching `/^WP_/`) are no longer falsely flagged as unqualified global class references. The sniff now checks surrounding token context (new, ::, instanceof, extends, type hints, catch, return types) and only flags identifiers used as class references.
- `Emrikol.Namespaces.GlobalNamespace` — return type auto-fix now prepends the backslash before the type name instead of before the colon. Previously `function foo(): Exception` would be fixed to `function foo()\: Exception` (backslash before colon); now correctly produces `function foo(): \Exception`.
- `Emrikol.Comments.DocblockTypeSync` — added test coverage for leading-backslash type drift (`\WP_Error` vs `WP_Error`). Added protective comment in `normalize_type_alias()` documenting why leading backslashes must not be stripped.

## [0.3.0] - 2026-02-22

### Added

- `Emrikol.Comments.InlineCommentPeriod` — fixable replacement for `Squiz.Commenting.InlineComment.InvalidEndChar`. Comments ending in a Unicode letter (`\p{L}`) get a period auto-appended via `phpcbf` (`AppendPeriod`); other invalid endings are reported as non-fixable (`InvalidEndChar`). PHPCS directives (`phpcs:ignore`, `phpcs:disable`, etc.) are skipped. Configurable extra accepted closers via three properties: `extra_accepted_closers` (literal characters), `extra_accepted_pattern` (PCRE Unicode property classes), and `extra_accepted_hex_ranges` (Unicode codepoint ranges).

## [0.2.3] - 2026-02-22

### Changed

- `Emrikol.Comments.PhpcsDirective` — `UnmatchedDisable` demoted from error to warning. A targeted file-wide `phpcs:disable Specific.Sniff` is a common legitimate pattern. The warning message now recommends adding a matching `phpcs:enable` or moving the exclusion to `.phpcs.xml.dist`.

## [0.2.2] - 2026-02-22

### Fixed

- `Emrikol.Comments.DocblockTypeSync` — PHPDoc type aliases (`boolean`, `integer`, `double`, `real`, `callback`) are now normalized to their canonical PHP types (`bool`, `int`, `float`, `float`, `callable`) before comparison. Previously, `@param boolean` vs code `bool` would falsely trigger a `TypeDrift` warning.

## [0.2.1] - 2026-02-22

### Fixed

- `Emrikol.Namespaces.GlobalNamespace` — no longer flags namespace, class, interface, trait, or enum declarations as unqualified global class references. Previously, names like `WP_TimeSync` in `namespace WP_TimeSync;` or `class WP_TimeSync {}` would be falsely flagged (and the auto-fixer would produce invalid PHP like `namespace \WP_TimeSync;`).
- `Emrikol.Namespaces.GlobalNamespace` — fixer state (`flagged_errors`, `use_statements`) now resets correctly on each processing pass via `T_OPEN_TAG`. Previously, state persisted across fixer iterations, which could prevent fixes from being applied.
- `BaseSniffTestCase::check_file()` — sniff properties are now stored through the PHPCS ruleset property mechanism instead of direct object assignment. This ensures properties survive `populateTokenListeners()` sniff object recreation during fixer iterations, fixing silent fixer failures in tests.

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
