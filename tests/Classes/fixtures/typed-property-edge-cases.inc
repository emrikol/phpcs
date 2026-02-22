<?php

class EdgeCases {
	public static $items;
	var $old_style;
	public string $typed_static;
	public readonly string $readonly_prop;
	protected static int $typed_static_int = 0;
	public $with_default = 'hello';
}

class ConstructorPromotion {
	public function __construct(
		public $untyped_promoted,
		public string $typed_promoted,
	) {}
}

class TypedEdgeCases {
	public ?string $nullable = null;
	public string|int $union;
	public int $simple = 42;
}

// Constants are not T_VARIABLE — should never be processed.
class WithConstants {
	const FOO = 'foo';
	public const BAR = 42;
	protected const BAZ = true;
}

// Local variables should not be flagged (RuntimeException path).
function has_local_vars() {
	$local = 'hello';
	$another = 42;
	return $local;
}

// Trait properties follow same rules as class properties.
trait MyTrait {
	public $untyped_trait_prop;
	public string $typed_trait_prop;
}

// Anonymous class properties should be checked.
$anon = new class {
	public $untyped_anon;
	public int $typed_anon;
};

// Intersection types are valid type declarations.
class IntersectionTypes {
	public \ArrayAccess&\Countable $intersection;
}

// PHP 8.4: Asymmetric visibility.
class AsymmetricVisibility {
	public private(set) string $typed_asymmetric;
	public private(set) $untyped_asymmetric;
	protected(set) int $typed_protected_set;
	protected(set) $untyped_protected_set;
}

// PHP 8.4: Abstract properties with hooks.
abstract class AbstractProps {
	abstract public int $abstract_typed { get; }
	abstract public $abstract_untyped { get; }
}

// Multi-property declaration: only $b should be untyped.
class MultiProperty {
	public string $typed_a, $maybe_untyped_b;
}

// Enum — should NOT flag anything (cases are not properties).
enum Color {
	case RED;
	case GREEN;
	case BLUE;
}

// Backed enum — should NOT flag anything.
enum Status: string {
	case ACTIVE = 'active';
	case INACTIVE = 'inactive';
}

// Readonly class (PHP 8.2) — untyped properties should still be flagged.
readonly class ReadonlyClass {
	public string $typed_readonly_class;
	public $untyped_readonly_class;
}

// DNF types (PHP 8.2) — valid type declaration.
class DnfTypes {
	public (\Countable&\Iterator)|null $dnf_prop;
}

// var keyword WITH type — should NOT flag (type is present).
class VarWithType {
	var string $var_typed;
	var $var_untyped;
}
