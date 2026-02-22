# Emrikol PHPCS

Custom [PHP CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) standards for enforcing type safety, strict types, namespace declarations, and proper global class qualification in PHP projects.

Built to consolidate duplicated custom sniffs across multiple projects into a single, reusable, Composer-installable package.

## Installation

```bash
composer require --dev emrikol/phpcs
```

The standard is automatically registered with PHPCS via [phpcodesniffer-composer-installer](https://github.com/PHPCSStandards/composer-installer).

Verify it's available:

```bash
vendor/bin/phpcs -i
# Should include "Emrikol" in the list
```

## Quick Start

Create a `phpcs.xml.dist` in your project root:

**WordPress project:**

```xml
<?xml version="1.0"?>
<ruleset name="My WordPress Plugin">
    <rule ref="WordPress" />
    <rule ref="Emrikol" />

    <!-- WordPress presets for type validation and global class checking -->
    <rule ref="./vendor/emrikol/phpcs/presets/wordpress-classes.xml" />
    <rule ref="./vendor/emrikol/phpcs/presets/wordpress-global-classes.xml" />
    <rule ref="./vendor/emrikol/phpcs/presets/php-global-classes.xml" />
</ruleset>
```

**Non-WordPress project:**

```xml
<?xml version="1.0"?>
<ruleset name="My PHP Project">
    <rule ref="PSR12" />
    <rule ref="Emrikol" />

    <!-- Core PHP global classes only -->
    <rule ref="./vendor/emrikol/phpcs/presets/php-global-classes.xml" />
</ruleset>
```

Then run:

```bash
# Check for violations
vendor/bin/phpcs src/

# Auto-fix what's possible
vendor/bin/phpcbf src/
```

## Sniffs

### `Emrikol.Functions.TypeHinting`

Enforces that all function parameters have type declarations.

**Error code:** `MissingParameterType`

**Auto-fix:** Yes. When a `@param` docblock tag exists with a clean PHP type, `phpcbf` inserts the type hint automatically.

```php
// ERROR: Missing type declaration for parameter '$name'
function greet($name) {}

// OK
function greet(string $name) {}
```

**Auto-fix strategy (conservative):** Only fixes when the docblock type maps unambiguously:

| Docblock type | Auto-fixed to | Notes |
|---|---|---|
| `@param string $name` | `string $name` | Direct mapping |
| `@param integer $name` | `int $name` | Alias normalized |
| `@param boolean $flag` | `bool $flag` | Alias normalized |
| `@param string\|null $name` | `?string $name` | Nullable shorthand |
| `@param null\|string $name` | `?string $name` | Order doesn't matter |
| `@param WP_Post $post` | `WP_Post $post` | Class names preserved |
| `@param string[] $items` | `array $items` | Array notation collapsed |
| `@param string\|int $val` | *Not auto-fixed* | Multi-type union, needs manual review |

---

### `Emrikol.Functions.TypeDeclaration`

Enforces that all functions have return type declarations. Optionally validates that the return type is a known/valid type.

**Error codes:** `MissingReturnType`, `InvalidReturnType`

**Auto-fix:** Yes. When a `@return` docblock tag exists with a clean PHP type, `phpcbf` inserts the return type declaration automatically. Uses the same conservative strategy as `TypeHinting` above.

```php
// ERROR: Missing return type declaration for function 'getName'
function getName() { return $this->name; }

// OK
function getName(): string { return $this->name; }
```

**Excluded methods:** `__construct`, `__destruct`, `__clone` (these should not have return type declarations).

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `validate_types` | `bool` | `false` | When `true`, validates return types against built-in PHP types and the `known_classes` list. Reports `InvalidReturnType` for unrecognized types. |
| `known_classes` | `array` | `[]` | Class names considered valid return types when `validate_types` is enabled. Populate via `ruleset.xml` or include a preset. |

**Example — enable type validation with custom classes:**

```xml
<rule ref="Emrikol.Functions.TypeDeclaration">
    <properties>
        <property name="validate_types" value="true" />
        <property name="known_classes" type="array">
            <element value="MyApp\Models\User" />
            <element value="MyApp\Models\Post" />
        </property>
    </properties>
</rule>
```

Or use the bundled WordPress preset (see [Presets](#presets)).

---

### `Emrikol.Namespaces.GlobalNamespace`

Ensures that global classes used in namespaced code are properly qualified with a leading backslash (`\`) or imported via a `use` statement.

**Error codes:** `GlobalNamespaceTypeHint`, `GlobalNamespaceReturnType`, `GlobalNamespace`

**Auto-fix:** Yes. `phpcbf` prepends `\` before unqualified global class references.

```php
namespace MyPlugin;

// ERROR: Global class 'WP_Post' should be referenced with a leading backslash
function get_post(WP_Post $post): WP_Query {}

// OK (either approach)
function get_post(\WP_Post $post): \WP_Query {}

// Also OK
use WP_Post;
use WP_Query;
function get_post(WP_Post $post): WP_Query {}
```

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `known_global_classes` | `array` | `[]` | Explicit list of global class names to check. |
| `class_patterns` | `array` | `[]` | Regex patterns to match class names (e.g., `/^WP_/`). |

All three detection methods (explicit list, regex patterns, and presets) are combinable. A class triggers an error if it matches **any** of them.

**Example — custom class list and patterns:**

```xml
<rule ref="Emrikol.Namespaces.GlobalNamespace">
    <properties>
        <property name="known_global_classes" type="array">
            <element value="DateTime" />
            <element value="Exception" />
        </property>
        <property name="class_patterns" type="array">
            <element value="/^WP_/" />
            <element value="/^Acme_/" />
        </property>
    </properties>
</rule>
```

Or use the bundled presets (see [Presets](#presets)).

---

### `Emrikol.Namespaces.Namespace`

Enforces that every PHP file has a `namespace` declaration.

**Error code:** `MissingNamespace`

**Auto-fix:** No. Namespace choice requires human judgment (directory structure, PSR-4 mapping, etc.).

```php
// ERROR: PHP file must have a namespace declaration.
<?php
class MyClass {}

// OK
<?php
namespace MyPlugin;
class MyClass {}
```

---

### `Emrikol.PHP.StrictTypes`

Enforces that every PHP file contains `declare(strict_types=1);`.

**Error code:** `MissingStrictTypes`

**Auto-fix:** Yes. `phpcbf` inserts `declare(strict_types=1);` after the opening `<?php` tag and any file-level docblock.

```php
// ERROR: PHP file must contain declare(strict_types=1);
<?php
namespace MyPlugin;

// OK
<?php
declare(strict_types=1);
namespace MyPlugin;

// Also OK (docblock before declare)
<?php
/**
 * Plugin Name: My Plugin
 */
declare(strict_types=1);
namespace MyPlugin;
```

---

## Presets

Presets are drop-in XML files that configure sniff properties with curated class lists. Include them in your `ruleset.xml` to avoid manually listing classes.

### `presets/wordpress-classes.xml`

Configures `TypeDeclarationSniff` with `validate_types` enabled and all known `WP_*` classes as valid return types. Auto-generated from WordPress core source.

```xml
<rule ref="./vendor/emrikol/phpcs/presets/wordpress-classes.xml" />
```

### `presets/wordpress-global-classes.xml`

Configures `GlobalNamespaceSniff` with a `/^WP_/` regex pattern to catch any WordPress class used without proper qualification in namespaced code.

```xml
<rule ref="./vendor/emrikol/phpcs/presets/wordpress-global-classes.xml" />
```

### `presets/php-global-classes.xml`

Configures `GlobalNamespaceSniff` with ~75 common global PHP classes (`DateTime`, `Exception`, `PDO`, SPL classes, Reflection classes, etc.).

```xml
<rule ref="./vendor/emrikol/phpcs/presets/php-global-classes.xml" />
```

Presets are combinable. For WordPress projects, include all three:

```xml
<rule ref="./vendor/emrikol/phpcs/presets/wordpress-classes.xml" />
<rule ref="./vendor/emrikol/phpcs/presets/wordpress-global-classes.xml" />
<rule ref="./vendor/emrikol/phpcs/presets/php-global-classes.xml" />
```

---

## Disabling Individual Sniffs

Disable a specific sniff entirely:

```xml
<rule ref="Emrikol">
    <exclude name="Emrikol.PHP.StrictTypes" />
</rule>
```

Disable inline for a specific line:

```php
$result = some_function($untyped_param); // phpcs:ignore Emrikol.Functions.TypeHinting.MissingParameterType
```

---

## Updating the WordPress Class List

The `presets/wordpress-classes.xml` file contains an explicit list of `WP_*` classes extracted from WordPress core. This list should be regenerated when a new major WordPress version is released.

A fully automated script is included:

```bash
./bin/generate-wp-class-list.sh
```

The script:

1. Downloads the latest WordPress nightly from `https://wordpress.org/nightly-builds/wordpress-latest.zip`
2. Extracts and scans all PHP files for `WP_*` class definitions
3. Regenerates `presets/wordpress-classes.xml` with the updated list
4. Prints a summary including the WordPress version and class count

**Requirements:** `curl`, `unzip`, `grep`, `sed`, `sort` (all standard on macOS and Linux).

**When to run:**

- After each major WordPress release (e.g., 7.0, 7.1)
- Before tagging a new release of this package
- Optionally in CI to detect drift

After running, review the changes and commit:

```bash
./bin/generate-wp-class-list.sh
git diff presets/wordpress-classes.xml
git add presets/wordpress-classes.xml
git commit -m "Update WordPress class list for WP X.Y"
```

---

## Development

### Git Hooks

A pre-push hook is included that prevents pushing version tags without a valid `CHANGELOG.md` entry. To install:

```bash
git config core.hooksPath hooks
```

Or copy manually:

```bash
cp hooks/pre-push .git/hooks/pre-push && chmod +x .git/hooks/pre-push
```

The hook validates that:

- `CHANGELOG.md` exists
- A `## [X.Y.Z] - YYYY-MM-DD` entry exists for the version being tagged
- The date is not `TBD`

---

## Requirements

- PHP >= 8.0
- PHP CodeSniffer >= 3.7

## License

GPL-3.0-or-later
