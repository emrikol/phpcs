<?php
declare(strict_types=1);

namespace MyApp;

// Line 7: 1 error for $name
function greet($name): string {
	return "Hello, $name";
}

// Line 12: 2 errors for $a and $b
function add($a, $b): int {
	return $a + $b;
}

// Line 17: 1 error for $items (callable is typed)
function mixed_params($items, callable $callback): void {}

// Line 21: 1 error for $value
class Example {
	public function method($value): void {}
}
