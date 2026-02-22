<?php
// All functions in this file have correct, complete docblocks.
// No errors or warnings should be emitted.

/**
 * Simple function with all types matching.
 *
 * @param string $name The name.
 *
 * @return string
 */
function correct_basic( string $name ): string {
	return $name;
}

/**
 * Multiple parameters, all documented.
 *
 * @param string $name  The name.
 * @param int    $count The count.
 *
 * @return bool
 */
function correct_multi( string $name, int $count ): bool {
	return strlen( $name ) > $count;
}

/**
 * Nullable parameter and return using |null notation.
 *
 * @param string|null $value The value.
 *
 * @return int|null
 */
function correct_nullable( ?string $value ): ?int {
	return null === $value ? null : strlen( $value );
}

/**
 * Void return.
 *
 * @param string $message The message.
 *
 * @return void
 */
function correct_void( string $message ): void {
	echo $message;
}

/**
 * Union parameter types.
 *
 * @param int|string $value The value.
 *
 * @return mixed
 */
function correct_union( int|string $value ): mixed {
	return $value;
}

/**
 * Self return type.
 *
 * @param string $name The name.
 *
 * @return self
 */
function correct_self( string $name ): self {
	return new self();
}

/**
 * Static return type.
 *
 * @param string $name The name.
 *
 * @return static
 */
function correct_static( string $name ): static {
	return new static();
}

/**
 * Never return type.
 *
 * @param string $message The message.
 *
 * @return never
 */
function correct_never( string $message ): never {
	throw new \RuntimeException( $message );
}

/**
 * Array param and return.
 *
 * @param array $items The items.
 *
 * @return array
 */
function correct_array( array $items ): array {
	return $items;
}

/**
 * Callable param.
 *
 * @param callable $callback The callback.
 *
 * @return void
 */
function correct_callable( callable $callback ): void {
	$callback();
}

/**
 * Function with default parameter value.
 *
 * @param string $name The name.
 * @param int    $count The count.
 *
 * @return string
 */
function correct_defaults( string $name, int $count = 10 ): string {
	return str_repeat( $name, $count );
}

// No typed parameters and no return type — should be skipped entirely.
function no_types( $a, $b ) {
	return $a + $b;
}

/**
 * Only untyped params with a return type.
 *
 * @return int
 */
function only_return_typed( $a, $b ): int {
	return $a + $b;
}

class CorrectClass {

	/**
	 * Constructor with typed params.
	 *
	 * @param string $name The name.
	 * @param int    $age  The age.
	 */
	public function __construct( string $name, int $age ) {
	}

	/**
	 * Method with all types.
	 *
	 * @param float $rate The rate.
	 *
	 * @return bool
	 */
	public function process( float $rate ): bool {
		return $rate > 0;
	}

	/**
	 * Static method.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public static function exists( string $key ): bool {
		return false;
	}

	/**
	 * Final method.
	 *
	 * @param string $input The input.
	 *
	 * @return string
	 */
	final public function transform( string $input ): string {
		return $input;
	}
}

/**
 * Function with mixed typed and untyped params.
 * The untyped one has a @param with no type — that's fine.
 *
 * @param        $untyped The untyped param.
 * @param string $typed   The typed param.
 *
 * @return void
 */
function mixed_typed_untyped( $untyped, string $typed ): void {
}

/**
 * Function with only a return type, no parameters.
 *
 * @return string
 */
function return_only(): string {
	return 'hello';
}

/**
 * Function with @throws and @since tags interspersed — should not trigger errors.
 *
 * @since 1.0.0
 *
 * @param string $value The value.
 *
 * @return string
 *
 * @throws \RuntimeException When something fails.
 */
function with_extra_tags( string $value ): string {
	return $value;
}

/**
 * Multiline function signature.
 *
 * @param string $first  First param.
 * @param int    $second Second param.
 * @param bool   $third  Third param.
 *
 * @return string
 */
function multiline_signature(
	string $first,
	int $second,
	bool $third
): string {
	return $first;
}
