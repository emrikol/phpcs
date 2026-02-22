<?php
// Edge cases: constructors, abstract, variadic, inheritdoc, closures, etc.

class EdgeCaseClass {

	/**
	 * Constructor — no @return needed, but @param should be present.
	 *
	 * @param string $name The name.
	 */
	public function __construct( string $name ) {
	}

	/**
	 * Destructor — no @return needed and no params.
	 */
	public function __destruct() {
	}

	/**
	 * Clone — no @return needed.
	 */
	public function __clone() {
	}
}

abstract class AbstractClass {

	/**
	 * Abstract method with typed params and return.
	 *
	 * @param string $value The value.
	 *
	 * @return int
	 */
	abstract public function abstract_method( string $value ): int;
}

interface SomeInterface {

	/**
	 * Interface method with types.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function interface_method( string $key ): bool;
}

class InheritDocClass {

	/**
	 * {@inheritdoc}
	 */
	public function inherited_method( string $name ): string {
		return $name;
	}

	/**
	 * {@INHERITDOC}
	 */
	public function inherited_uppercase( string $name ): string {
		return $name;
	}
}

/**
 * Variadic parameter — should use the element type.
 *
 * @param string $items The items.
 *
 * @return void
 */
function variadic_param( string ...$items ): void {
}

// Constructor promotion — should be processed normally.
class PromotionClass {

	/**
	 * Constructor with promoted params.
	 *
	 * @param string $name The name.
	 * @param int    $age  The age.
	 */
	public function __construct(
		public readonly string $name,
		protected int $age,
	) {
	}
}

// Function with only untyped params — no errors.
function all_untyped( $a, $b ) {
	return $a + $b;
}

/**
 * Function with extra tags that should be left untouched.
 *
 * @since 1.0.0
 *
 * @param string $value The value.
 *
 * @return string
 *
 * @throws \Exception When something fails.
 */
function extra_tags( string $value ): string {
	return $value;
}

// Constructor missing @param tag — should be fixable.
class ConstructorMissingParam {

	/**
	 * Constructor.
	 */
	public function __construct( string $name, int $age ) {
	}
}

// Closures and arrow functions should NOT be flagged (T_FUNCTION only).
$closure = function ( string $name ): string {
	return $name;
};

$arrow = fn( string $name ): string => $name;

// Function right after closure — docblock should NOT attach to closure.
function after_closure( string $name ): void {
}

// Multiline function signature.
function multiline_no_docblock(
	string $first,
	int $second,
	bool $third
): string {
	return $first;
}

// Function with by-reference typed param.
/**
 * By-reference parameter.
 *
 * @param string $value The value.
 *
 * @return void
 */
function by_reference( string &$value ): void {
	$value .= ' modified';
}

// Docblock for class that is NOT a function docblock.
/**
 * This class docblock should not be treated as a function docblock.
 */
class ClassWithDocblock {

	public function method_no_doc( string $value ): int {
		return strlen( $value );
	}
}

// Interleaved functions to test no docblock crosstalk.
/**
 * First in sequence.
 *
 * @param string $a The a.
 *
 * @return void
 */
function sequence_first( string $a ): void {
}

function sequence_second_no_doc( string $b ): void {
}

/**
 * Third in sequence.
 *
 * @param string $c The c.
 *
 * @return void
 */
function sequence_third( string $c ): void {
}
