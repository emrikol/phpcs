# Emrikol PHPCS

Custom [PHP CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) standards for enforcing type safety, strict types, namespace declarations, proper global class qualification, and security hardening in PHP and WordPress projects.

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
</ruleset>
```

**Non-WordPress project:**

```xml
<?xml version="1.0"?>
<ruleset name="My PHP Project">
    <rule ref="PSR12" />
    <rule ref="Emrikol" />
</ruleset>
```

> **Note:** PHP built-in classes (e.g., `DateTime`, `Exception`, `PDO`) are auto-detected at runtime — no preset file needed. The `php-global-classes.xml` preset is still available if you want to pin to a specific list (see [Presets](#presets)).

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
| `auto_detect_php_classes` | `bool` | `true` | When `true`, auto-detects PHP built-in classes at runtime and treats them as valid return types (when `validate_types` is enabled). Set to `false` to rely solely on `known_classes` and presets. |

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
| `auto_detect_php_classes` | `bool` | `true` | When `true`, auto-detects PHP built-in classes at runtime (e.g., `DateTime`, `Exception`). Set to `false` to disable and rely solely on explicit lists, patterns, and presets. |

All four detection methods (explicit list, regex patterns, presets, and auto-detection) are combinable. A class triggers an error if it matches **any** of them.

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

Some files intentionally live in the global namespace (bootstrap files, WordPress plugin entry points, helper files). Exclude them in your `phpcs.xml.dist`:

```xml
<rule ref="Emrikol.Namespaces.Namespace">
    <exclude-pattern>src/bootstrap.php</exclude-pattern>
    <exclude-pattern>src/helpers.php</exclude-pattern>
    <exclude-pattern>my-plugin.php</exclude-pattern>
</rule>
```

---

### `Emrikol.Classes.TypedProperty`

Enforces that all class properties have type declarations.

**Error code:** `MissingPropertyType`

**Auto-fix:** No. Property type choice requires human judgment.

```php
// ERROR: Missing type declaration for property '$name'
class User {
    public $name;
    protected $age;
}

// OK
class User {
    public string $name;
    protected int $age;
    public readonly string $id;
    public static int $count = 0;
}
```

Constructor promotion parameters are skipped (handled by `TypeHinting`). Constants are not affected.

---

### `Emrikol.PHP.DiscouragedMixedType`

Warns when `mixed` is used as a type declaration for function parameters, return types, or property types.

**Warning codes:** `MixedParameterType`, `MixedReturnType`, `MixedPropertyType`

**Auto-fix:** No. Uses `addWarning()` (not `addError()`) since `mixed` is sometimes necessary for third-party code compatibility. Suppress with `phpcs:ignore` when genuinely required.

```php
// WARNING: Parameter '$input' uses the 'mixed' type
function process(mixed $input): mixed { return $input; }

// OK — specific types
function process(string $input): string { return $input; }

// Suppress when necessary
/** @phpcs:ignore Emrikol.PHP.DiscouragedMixedType.MixedParameterType */
function handle(mixed $data): void {}
```

Covers `T_FUNCTION`, `T_CLOSURE`, and `T_FN` (arrow functions) for complete coverage.

---

### `Emrikol.Comments.PhpcsDirective`

Enforces surgical use of PHPCS suppression directives. Bare (un-scoped) directives and file-level suppression are flagged. Unmatched disable/enable pairs are detected. Missing or malformed `--` note separators are caught and auto-fixed.

**Error codes:** `BareIgnore`, `BareDisable`, `UnmatchedDisable`, `IgnoreFile`, `DeprecatedIgnoreLine`, `DeprecatedIgnoreStart`, `DeprecatedIgnoreEnd`, `MissingNoteSeparator`, `MalformedNoteSeparator`

**Warning codes:** `UnmatchedEnable`

**Auto-fix:** Yes. Legacy `@codingStandardsIgnore*` directives are converted to modern equivalents. Missing `--` note separators are inserted. Malformed separators (e.g., `Code--note`) are normalized to `Code -- note`.

```php
// ERROR: phpcs:ignore directive must specify sniff code(s)
// phpcs:ignore
$x = 1;

// OK — surgical suppression
// phpcs:ignore Emrikol.Functions.TypeHinting.MissingParameterType
$x = 1;

// ERROR: phpcs:disable without matching phpcs:enable
// phpcs:disable Emrikol.PHP.StrictTypes

// OK — matched pair
// phpcs:disable Emrikol.PHP.StrictTypes
$x = 1;
// phpcs:enable Emrikol.PHP.StrictTypes

// ERROR (auto-fixable): Missing note separator
// phpcs:ignore Foo.Bar reason text here
// Fixed to: phpcs:ignore Foo.Bar -- reason text here

// ERROR (auto-fixable): Malformed separator
// phpcs:ignore Foo.Bar--note
// Fixed to: phpcs:ignore Foo.Bar -- note

// WARNING: Enable without matching disable
// phpcs:enable Some.Sniff.NeverDisabled

// ERROR (auto-fixable): Deprecated directive
// @codingStandardsIgnoreLine  →  phpcs:ignore
```

| Rule | Type | Description |
|---|---|---|
| Bare `phpcs:ignore` | Error | Must specify sniff code(s) |
| Bare `phpcs:disable` | Error | Must specify sniff code(s) |
| `phpcs:disable` without `phpcs:enable` | Error | Must have matching enable before EOF |
| `phpcs:enable` without prior `phpcs:disable` | Warning | May indicate stale or misplaced directive |
| `phpcs:ignore-file` | Error | Always forbidden |
| Missing `--` note separator | Error (fixable) | Notes after sniff codes need `--` separator |
| Malformed `--` separator | Error (fixable) | `--` needs surrounding spaces to be recognized |
| `@codingStandardsIgnore*` | Error (fixable) | Auto-fixed to modern equivalents |

Errors and warnings from this sniff cannot be suppressed by `phpcs:ignore` on the same line (suppression bypass).

**Note on cascading errors:** A missing `--` separator corrupts the sniff code that PHPCS parses (e.g., `phpcs:enable Foo.Bar done with this` becomes code `Foo.Bar done with this`). If the corresponding `phpcs:disable Foo.Bar` uses the correct code, the enable won't match, producing both `MissingNoteSeparator` and `UnmatchedDisable`. Running `phpcbf` fixes the separator, which resolves the mismatch.

---

### `Emrikol.Comments.DocblockTypeSync`

Ensures that docblock `@param`/`@return` tags match code-side type declarations. If a function has typed parameters or a return type, the docblock should reflect those types. Creates missing docblocks, adds missing tags, fills in missing types, and warns on type drift.

**Error codes:** `MissingParamType`, `MissingParamTag`, `MissingReturnType`, `MissingReturnTag`, `MissingDocblock`

**Warning codes:** `TypeDrift`

**Auto-fix:** Yes for all error codes. `TypeDrift` is a warning only (developer must decide which is correct).

```php
// ERROR (MissingDocblock): No docblock for typed function
function greet(string $name): string { return "Hello, $name"; }

// ERROR (MissingParamType): @param exists but has no type
/**
 * @param $name The name.
 */
function greet(string $name): string {}

// ERROR (MissingParamTag): Docblock exists but @param tag is missing
/**
 * @return string
 */
function greet(string $name): string {}

// ERROR (MissingReturnTag): Docblock exists but @return tag is missing
/**
 * @param string $name The name.
 */
function greet(string $name): string {}

// WARNING (TypeDrift): Docblock type contradicts code type
/**
 * @param int $name The name.
 */
function greet(string $name): string {}

// OK — types match
/**
 * @param string $name The name.
 *
 * @return string
 */
function greet(string $name): string {}
```

**Specialization detection:** The sniff recognizes when a docblock type is a valid refinement of the code type and does not flag it as drift:

| Code type | Docblock type | Status |
|---|---|---|
| `array` | `string[]`, `array<string, int>` | Valid specialization |
| `object` | `\WP_Post` | Valid specialization |
| `callable` | `\Closure`, `callable(string): int` | Valid specialization |
| `iterable` | `iterable<string>` | Valid specialization |
| `?array` | `string[]\|null` | Valid (nullable specialization) |
| `string` | `int` | TypeDrift warning |

**Nullable conversion:** `?string` in code becomes `string|null` in generated docblocks (PHPDoc convention).

**Skipped cases:** Functions with no typed parameters and no return type, `{@inheritdoc}` docblocks, closures/arrow functions (T_FUNCTION only), `__construct`/`__destruct`/`__clone` return types.

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `generate_missing_docblocks` | `bool` | `true` | Set `false` to only fix existing docblocks (suppresses `MissingDocblock`). |
| `report_type_drift` | `bool` | `true` | Set `false` to suppress `TypeDrift` warnings. |

**Example — disable docblock generation:**

```xml
<rule ref="Emrikol.Comments.DocblockTypeSync">
    <properties>
        <property name="generate_missing_docblocks" value="false" />
    </properties>
</rule>
```

---

### `Emrikol.WordPress.NoHookClosure`

Forbids closures and arrow functions as WordPress hook callbacks. Closures passed to `add_action()`/`add_filter()` cannot be unhooked with `remove_action()`/`remove_filter()`, breaking WordPress extensibility.

**Error codes:** `ClosureHookCallback`, `ClosureWrapperCallback`, `FirstClassCallableCallback`, `AnonymousClassCallback`

**Warning codes:** `VariableCallback`, `IndirectHookRegistration`

**Auto-fix:** No. Converting a closure to a named function requires human judgment.

```php
// ERROR: Closure used as callback for 'add_action'
add_action( 'init', function() {
    do_something();
} );

// ERROR: Arrow function used as callback
add_filter( 'the_content', fn( $content ) => $content . ' suffix' );

// ERROR: First-class callable creates a Closure object
add_action( 'init', my_function(...) );

// ERROR: Closure wrapper returns a Closure
add_action( 'init', Closure::fromCallable( 'my_function' ) );

// ERROR: Anonymous class can't be unhooked
add_action( 'init', new class { public function __invoke() {} } );

// WARNING: Variable callback — can't verify at static analysis time
add_action( 'init', $callback );

// WARNING: Indirect hook registration bypasses static checks
call_user_func( 'add_action', 'init', 'my_function' );

// OK — named function
add_action( 'init', 'my_init_function' );

// OK — method reference
add_action( 'init', array( $this, 'init_method' ) );
add_filter( 'the_content', [ $this, 'filter_content' ] );
```

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `hook_functions` | `string` | `add_action,add_filter` | Comma-separated list of hook registration functions to check. Add custom wrappers as needed. |

**Example — add custom hook wrappers:**

```xml
<rule ref="Emrikol.WordPress.NoHookClosure">
    <properties>
        <property name="hook_functions" value="add_action,add_filter,my_add_action" />
    </properties>
</rule>
```

#### Known limitations

This sniff performs static analysis and cannot detect closures hidden behind dynamic patterns:

| Pattern | Detected? | Notes |
|---|---|---|
| `add_action( 'hook', function() {} )` | Yes | Direct closure |
| `add_action( 'hook', fn() => ... )` | Yes | Direct arrow function |
| `add_action( 'hook', my_func(...) )` | Yes | First-class callable |
| `add_action( 'hook', Closure::fromCallable(...) )` | Yes | Closure wrapper |
| `add_action( 'hook', new class { ... } )` | Yes | Anonymous class |
| `add_action( 'hook', $callback )` | Warning | Could be a closure or named function — can't tell statically |
| `add_action( 'hook', $obj->getCallback() )` | No | Method return value is opaque at analysis time |
| `add_action( 'hook', $debug ? fn() => {} : 'func' )` | No | Ternary/conditional expressions not analyzed |
| `add_action( 'hook', $callbacks['key'] )` | No | Array access not analyzed |
| `call_user_func( 'add_action', ... )` | Warning | Indirect registration detected but callback not inspected |

If you need stricter enforcement, pair this sniff with runtime checks or code review policies for the patterns above.

---

### `Emrikol.WordPress.RequirePermissionCallback`

Requires that `register_rest_route()` calls include a `permission_callback` in the args array. REST API endpoints without explicit permission callbacks default to public access, which is a security risk. WordPress 5.5+ logs a `_doing_it_wrong` notice when `permission_callback` is missing.

**Error code:** `MissingPermissionCallback`

**Auto-fix:** No. Permission logic requires human judgment.

```php
// ERROR: Missing 'permission_callback' in register_rest_route() args
register_rest_route( 'myplugin/v1', '/items', array(
    'methods'  => 'GET',
    'callback' => 'my_get_items',
) );

// OK — explicit permission callback
register_rest_route( 'myplugin/v1', '/items', array(
    'methods'             => 'GET',
    'callback'            => 'my_get_items',
    'permission_callback' => function() {
        return current_user_can( 'edit_posts' );
    },
) );

// OK — __return_true for intentionally public endpoints
register_rest_route( 'myplugin/v1', '/public', array(
    'methods'             => 'GET',
    'callback'            => 'my_public_endpoint',
    'permission_callback' => '__return_true',
) );
```

Handles both single-route and multi-route formats:

```php
// Multi-route: each sub-array is checked individually
register_rest_route( 'myplugin/v1', '/items', array(
    array(
        'methods'             => 'GET',
        'callback'            => 'my_get_items',
        'permission_callback' => '__return_true',  // OK
    ),
    array(
        'methods'  => 'POST',
        'callback' => 'my_create_item',
        // ERROR: this sub-array is missing permission_callback
    ),
) );
```

Variable and function-call args are skipped (can't be checked statically).

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `route_functions` | `string` | `register_rest_route` | Comma-separated list of route registration functions to check. Add custom wrappers as needed. |

---

### `Emrikol.PHP.DisallowSuperglobalWrite`

Forbids writing to PHP superglobals (`$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_COOKIE`, `$_SESSION`, `$_FILES`, `$_ENV`). Mutating superglobals creates hidden side-effects and breaks the principle of immutable input.

**Error codes:** `SuperglobalWrite`, `SuperglobalUnset`

**Auto-fix:** No. Refactoring superglobal writes requires human judgment.

```php
// ERROR: Direct write to superglobal '$_GET'
$_GET['injected'] = 'value';

// ERROR: Compound assignment
$_POST['data'] .= 'appended';

// ERROR: Unset superglobal
unset( $_GET['key'] );

// OK — reading superglobals
$name = $_GET['name'];
$data = sanitize_text_field( $_POST['input'] );
if ( isset( $_GET['action'] ) ) { ... }
```

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `superglobals` | `string` | `$_GET,$_POST,$_REQUEST,$_SERVER,$_COOKIE,$_SESSION,$_FILES,$_ENV` | Comma-separated list of superglobals to monitor for writes. |

---

### `Emrikol.Comments.BlockComment`

Fixable replacement for `Squiz.Commenting.BlockComment` with enhanced comment style enforcement. Extends the Squiz sniff with three capabilities:

1. **Hash comments** (`#`) are converted to `//` format.
2. **Consecutive `//` comments** (configurable threshold, default 2 lines) are converted to block comments.
   - Produces `/** */` when immediately before a declaration (function, class, interface, trait, enum, property, const, etc.).
   - Produces `/* */` for all other comment blocks.
3. **All existing Squiz block comment formatting checks** are preserved via parent delegation.

**Error codes:** `WrongStyle` (fixable), `HashComment` (fixable), plus all inherited `Squiz.Commenting.BlockComment.*` codes

**Auto-fix:** Yes. `phpcbf` converts `#` to `//`, then converts consecutive `//` to block comments, then Squiz formatting checks run on the result (multi-pass).

```php
// ERROR (WrongStyle, fixable): Consecutive // should be a block comment
// This is a long explanation.
// It spans multiple lines.
$foo = 'bar';

// Fixed to:
/*
 * This is a long explanation.
 * It spans multiple lines.
 */
$foo = 'bar';

// ERROR (WrongStyle, fixable): Before a function → produces /** docblock
// Greet the user.
// Returns the greeting string.
function greet( string $name ): string {}

// Fixed to:
/**
 * Greet the user.
 * Returns the greeting string.
 */
function greet( string $name ): string {}

// ERROR (HashComment, fixable): # comments converted to //
# This is a hash comment.

// Fixed to:
// This is a hash comment.

// OK — single line (below threshold)
// This is fine.

// OK — inline end-of-line comments are never grouped
$x = 1; // Inline comment.
$y = 2; // Another inline.
```

**Safety guards:** The fixer skips conversion when:

- Comment text contains `*/` (would produce invalid PHP by prematurely closing the block comment).
- All lines are bare `//` with no text (empty block comments are deleted by the Squiz parent, causing a fixer conflict loop).
- Any line contains a PHPCS directive (`// phpcs:ignore`, etc.).

**Declaration detection** for `/**` vs `/*`: Looks past whitespace and PHP 8.0+ attributes (`#[...]`) to find the next significant token. Recognized declaration keywords: `function`, `class`, `interface`, `trait`, `enum`, `const`, `var`, `public`, `protected`, `private`, `abstract`, `final`, `static`, `readonly`.

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `min_lines` | `int` | `2` | Minimum consecutive `//` comment lines to trigger conversion. Set to `3` or higher for less aggressive grouping. |

**Example — require 3+ lines before converting:**

```xml
<rule ref="Emrikol.Comments.BlockComment">
    <properties>
        <property name="min_lines" value="3" />
    </properties>
</rule>
```

---

### `Emrikol.Comments.InlineCommentPeriod`

Fixable replacement for `Squiz.Commenting.InlineComment.InvalidEndChar`. Inline comments starting with a Unicode letter must end in `.`, `!`, or `?`. Comments ending in a letter get a period auto-appended; other invalid endings are reported as non-fixable.

**Error codes:** `AppendPeriod` (fixable), `InvalidEndChar` (non-fixable)

**Auto-fix:** Yes for `AppendPeriod` — `phpcbf` appends a period to comments ending in a letter.

```php
// ERROR (AppendPeriod, fixable): Ends in a letter
// This is a comment

// ERROR (InvalidEndChar, non-fixable): Ends in a digit
// PHP 8

// OK — valid endings
// This is a comment.
// This is exciting!
// Is this valid?
```

PHPCS directives (`phpcs:ignore`, `phpcs:disable`, `phpcs:enable`) and non-prose comments (starting with digits, symbols, `@`) are skipped. Consecutive `//` comment lines are aggregated into blocks — only the last line's ending is checked. Comments after closing braces (`} // end if`) are skipped.

#### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `extra_accepted_closers` | `string` | `''` | Literal characters to accept as valid closers. Each character in the string is a closer. |
| `extra_accepted_pattern` | `string` | `''` | PCRE character class content (e.g., `\p{So}\p{Sm}`). Placed inside `/[...]$/u`. |
| `extra_accepted_hex_ranges` | `string` | `''` | Comma-separated Unicode hex ranges (e.g., `0x1F600-0x1F64F,0x2600-0x26FF`). |

**Example — allow common code punctuation:**

```xml
<rule ref="Emrikol.Comments.InlineCommentPeriod">
    <properties>
        <property name="extra_accepted_closers" value=":;)]}>" />
    </properties>
</rule>
```

**Example — allow emoji and math symbols:**

```xml
<rule ref="Emrikol.Comments.InlineCommentPeriod">
    <properties>
        <property name="extra_accepted_pattern" value="\p{So}\p{Sm}" />
    </properties>
</rule>
```

**Example — allow specific emoji blocks by codepoint range:**

```xml
<rule ref="Emrikol.Comments.InlineCommentPeriod">
    <properties>
        <property name="extra_accepted_hex_ranges" value="0x1F600-0x1F64F,0x2600-0x26FF" />
    </properties>
</rule>
```

Common [PCRE Unicode property classes](https://www.php.net/manual/en/regexp.reference.unicode.php): `\p{So}` (symbols/emoji), `\p{Sm}` (math symbols), `\p{Sc}` (currency), `\p{N}` (numbers), `\p{P}` (any punctuation).

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

Configures `GlobalNamespaceSniff` with all built-in PHP classes and interfaces across currently supported PHP versions. Auto-generated from official PHP Docker images using `bin/generate-php-class-list.sh`.

> **Note:** Since built-in PHP classes are now auto-detected at runtime by default, this preset is only needed if you want to pin to a specific class list (e.g., for reproducibility across environments) or if you've set `auto_detect_php_classes` to `false`.

```xml
<rule ref="./vendor/emrikol/phpcs/presets/php-global-classes.xml" />
```

### `presets/php-X.Y-classes.xml`

Per-version presets are also generated (e.g., `php-8.2-classes.xml`, `php-8.3-classes.xml`, etc.) if you need to target a specific PHP version rather than the combined list. Stale presets for EOL PHP versions are automatically removed when the generator runs.

```xml
<!-- Use a specific PHP version's classes instead of the combined list -->
<rule ref="./vendor/emrikol/phpcs/presets/php-8.4-classes.xml" />
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

## Updating the PHP Class List

The `presets/php-global-classes.xml` and per-version `presets/php-X.Y-classes.xml` files are auto-generated from official PHP Docker images. These should be regenerated when PHP versions reach end-of-life or new versions are released.

```bash
./bin/generate-php-class-list.sh
```

The script:

1. Queries the [endoflife.date API](https://endoflife.date/api/php.json) for currently supported PHP versions (or accepts specific versions as arguments)
2. Pulls official `php:X.Y-cli` Docker images for each version
3. Runs `get_declared_classes()` + `get_declared_interfaces()` filtered to built-in classes via `ReflectionClass`
4. Generates `presets/php-global-classes.xml` (union of all versions)
5. Generates `presets/php-X.Y-classes.xml` per version
6. Removes stale per-version presets for EOL PHP versions

**Requirements:** Docker (running), `curl`, and either `jq` or `python3` (for API parsing).

```bash
# Scan all currently supported PHP versions (auto-detected)
./bin/generate-php-class-list.sh

# Scan specific versions only
./bin/generate-php-class-list.sh 8.3 8.4
```

After running, review the changes and commit:

```bash
./bin/generate-php-class-list.sh
git diff presets/
git add presets/php-*-classes.xml presets/php-global-classes.xml
git commit -m "Update PHP class lists"
```

---

## Monorepo Linting (`phpcs-lint.sh`)

A PHPCS wrapper script that discovers and chains `.phpcs.xml.dist` configs up the directory tree. Designed for monorepos where subdirectories (e.g., individual plugins) need their own PHPCS configuration (prefixes, text domains) while inheriting rules from a parent config.

### How it works

1. Walks up from the target directory to the outermost git root (or disk root).
2. Collects all `.phpcs.xml.dist` (or `.phpcs.xml`) files along the path.
3. Chains them via `--standard=parent,child` — child configs override parent properties.
4. Runs `phpcs` (or `phpcbf` with `--fix`) against the target.

When run without a target path, it lints the root config first, then auto-discovers and lints all child configs (skipping `vendor/` and `node_modules/`).

### Usage

```bash
# Lint the current directory (discovers all child configs)
bash vendor/emrikol/phpcs/bin/phpcs-lint.sh

# Lint a specific subdirectory (chains parent configs automatically)
bash vendor/emrikol/phpcs/bin/phpcs-lint.sh plugins/my-plugin

# Auto-fix a specific subdirectory
bash vendor/emrikol/phpcs/bin/phpcs-lint.sh --fix plugins/my-plugin

# Pass extra flags to phpcs
bash vendor/emrikol/phpcs/bin/phpcs-lint.sh -- --report=summary
```

### Composer integration

Add to your `composer.json`:

```json
{
    "scripts": {
        "lint": "bash vendor/emrikol/phpcs/bin/phpcs-lint.sh",
        "lint:fix": "bash vendor/emrikol/phpcs/bin/phpcs-lint.sh --fix"
    }
}
```

Then run `composer lint` or `composer lint:fix`.

### Example monorepo structure

```
my-monorepo/
├── .phpcs.xml.dist          ← Root config (shared rules, base standard)
├── plugins/
│   ├── plugin-a/
│   │   ├── .phpcs.xml.dist  ← Overrides prefixes, text domain for plugin-a
│   │   └── plugin-a.php
│   └── plugin-b/
│       ├── .phpcs.xml.dist  ← Overrides prefixes, text domain for plugin-b
│       └── plugin-b.php
└── vendor/
```

Running `phpcs-lint.sh plugins/plugin-a` chains `my-monorepo/.phpcs.xml.dist` + `plugins/plugin-a/.phpcs.xml.dist`, so plugin-a inherits all shared rules while using its own prefixes and text domain.

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
