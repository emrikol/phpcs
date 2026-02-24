# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.3] - 2026-02-24

### Fixed

- All preset XML files now use `extend="true"` on array properties (`known_classes`, `known_global_classes`, `class_patterns`). Previously, including a preset after a project-level `extend="true"` addition would silently overwrite project additions because the preset's non-extend write wins. With `extend="true"` on both sides, all lists accumulate regardless of XML order. `bin/generate-php-class-list.sh` updated to emit `extend="true"` in future regenerations.

## [0.5.2] - 2026-02-24

### Changed

- `bin/phpcs-lint.sh` — config chaining now stops when a `.phpcs.xml.dist` contains `<config name="phpcs-lint-root" value="true" />`. This lets a plugin that is its own git repo nested inside a larger monorepo declare itself as a self-contained root, preventing configs from the outer repo from being chained in. Without the marker, the original outermost-git-root fallback behavior is preserved.

## [0.5.1] - 2026-02-24

### Changed

- `Emrikol.Comments.BlockComment` — `NoEmptyLineAfter` (inherited from Squiz) is now excluded by default. Requiring a blank line between a block comment and the code it introduces breaks the visual pairing between comment and statement. Re-enable per-project with `<rule ref="Emrikol.Comments.BlockComment.NoEmptyLineAfter" />`.

## [0.5.0] - 2026-02-23

### Added

- `Emrikol.Comments.BlockComment` — fixable replacement for `Squiz.Commenting.BlockComment` with enhanced comment style enforcement. Extends the Squiz sniff with: (1) `#` comments converted to `//` (`HashComment`, fixable), (2) consecutive `//` comments converted to block comments (`WrongStyle`, fixable, configurable `min_lines` threshold, default 2), (3) `/**` docblock detection when comment group precedes a declaration (function, class, interface, trait, enum, const, property, etc. — skips past PHP 8.0+ attributes), (4) all existing Squiz block comment formatting checks preserved via parent delegation. Safety guards skip conversion when comment text contains `*/` (would produce invalid PHP) or all lines are bare `//` (empty blocks deleted by Squiz parent).

## [0.4.0] - 2026-02-23

### Added

- `bin/phpcs-lint.sh` — PHPCS wrapper script that discovers and chains `.phpcs.xml.dist` configs up the directory tree. Child configs override parent properties (prefixes, text domains) while inheriting all rules. Walks up to the outermost git root (or disk root), chains configs via `--standard=parent,child`. Supports `--fix` for phpcbf, target paths, and extra flags. Enables per-plugin prefix/text-domain configs in monorepos without hardcoded `<rule ref>` paths.

## [0.3.5] - 2026-02-23

### Fixed

- `Emrikol.PHP.StrictTypes` — phpcbf no longer auto-inserts `declare(strict_types=1)` into HTML template partials (files starting with HTML before the first `<?php` tag). The fixer was injecting the declaration inside mid-file PHP blocks, which is a PHP syntax error. Template files now receive a non-fixable error instead, since adding strict types automatically could change type coercion behavior in unexpected ways.

## [0.3.4] - 2026-02-23

### Fixed

- `Emrikol.Comments.DocblockTypeSync` — `@param` tags with pass-by-reference (`&$var`), variadic (`...$var`), or reference-variadic (`&...$var`) notation no longer produce false TypeDrift warnings. The `&` and `...` operators are now correctly excluded from the parsed type string in `parse_param_tags()`.

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
