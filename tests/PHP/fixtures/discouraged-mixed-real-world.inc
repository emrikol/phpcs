<?php

// Closure with use() clause and mixed return type — should warn.
$fn = function ( mixed $x ) use ( $some_var ): mixed {
	return $x;
};

// Comment between colon and return type — should still warn.
function comment_before_return( string $input ): /* return type */ mixed {
	return $input;
}

// Attribute on function parameter — should still detect mixed.
function with_attribute( #[SensitiveParameter] mixed $secret ): void {
}

// Attribute on function itself — should still detect mixed.
#[Pure]
function attributed_func( mixed $input ): mixed {
	return $input;
}

// Multiline params with mixed scattered through.
function multiline_params(
	string $first,
	mixed $second,
	int $third,
	mixed $fourth,
): void {
}

// Enum method with mixed parameter.
enum Status: string {
	case Active = 'active';

	public function process( mixed $data ): mixed {
		return $data;
	}
}

// Arrow function with use — should warn on both param and return.
$arrow_use = fn( mixed $x ) => $x;

// Closure with use clause, mixed param, no return type.
$closure_use_param = function ( mixed $x ) use ( $env ) {
	return $env . $x;
};

// Long chain: closure inside array with mixed.
$callbacks = [
	'handler' => function ( mixed $event ): void {
		// handle
	},
];

// Method returning mixed in anonymous class.
$anon = new class {
	public function handle(): mixed {
		return null;
	}
};

// Multiline return type with comment — should still detect.
function multiline_return(
	string $input
): /* always returns mixed */ mixed {
	return $input;
}
