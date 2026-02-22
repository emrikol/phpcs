<?php

class NoMixedTypes {
	public string $name;
	protected int $age;

	public function greet( string $name ): string {
		return 'Hello ' . $name;
	}

	public function compute( int $a, float $b ): float {
		return $a + $b;
	}
}

$closure = function ( string $x ): int {
	return strlen( $x );
};

$arrow = fn( int $x ): int => $x * 2;

// Function named "mixed" — should NOT trigger false positive.
function mixed(): string {
	return 'not a type';
}

// Class method named "mixed" — should NOT trigger false positive.
class MixedMethodName {
	public function mixed(): string {
		return 'not a type';
	}
}

// Property named $mixed with proper type — should NOT trigger.
class MixedPropertyName {
	public string $mixed;
	protected int $mixed_value;
}

// String literal 'mixed' — should NOT trigger.
function uses_mixed_string(): string {
	$type = 'mixed';
	return $type;
}

// Comment containing "mixed" — should NOT trigger.
// This function deals with mixed content types.
function documented_function(): int {
	return 42;
}
