<?php

class MixedTypes {
	public mixed $data;

	public function process( mixed $input ): mixed {
		return $input;
	}

	public function multi_param( string $name, mixed $value ): void {
	}

	public function mixed_return_only( string $input ): mixed {
		return $input;
	}

	public function multiple_mixed_params( mixed $a, mixed $b ): void {
	}
}

// Standalone function (not in class) with mixed.
function standalone_mixed( mixed $input ): mixed {
	return $input;
}

// Closure with mixed.
$closure = function ( mixed $x ): mixed {
	return $x;
};

// Arrow function with mixed.
$arrow = fn( mixed $x ): mixed => $x;

// Interface method with mixed.
interface MixedInterface {
	public function handle( mixed $data ): mixed;
}

// Abstract method with mixed.
abstract class MixedAbstract {
	abstract public function process( mixed $input ): mixed;
}

// Trait method with mixed.
trait MixedTrait {
	public function transform( mixed $val ): mixed {
		return $val;
	}
}

// Local variable assignment — should NOT warn.
function no_property_warning() {
	$mixed = 'not a property';
	return $mixed;
}

// Constructor promotion with mixed — should warn via getMethodParameters.
class PromotedMixed {
	public function __construct(
		public mixed $promoted_mixed,
		public string $promoted_string,
	) {}
}

// Variadic mixed parameter — should warn.
function variadic_mixed( mixed ...$args ): void {
}

// Static method with mixed — should warn.
class StaticMixed {
	public static function process( mixed $input ): mixed {
		return $input;
	}
}

// Readonly property with mixed — should warn.
class ReadonlyMixed {
	public readonly mixed $readonly_data;
}

// Nested closure with mixed — should warn on inner closure.
function outer_function(): void {
	$inner = function ( mixed $x ): mixed {
		return $x;
	};
}
