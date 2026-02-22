<?php
declare(strict_types=1);

namespace MyApp;

// Pass by reference — missing type
/**
 * @param string $value A value.
 */
function by_reference(&$value): void {}

// Variadic — missing type
/**
 * @param string $items Items.
 */
function variadic(...$items): void {}

// Mix of typed and untyped
function mixed_one(string $typed, $untyped): void {}



// No docblock at all — error but NOT fixable
function no_docblock_at_all($param): void {}

// Multiple params, all untyped, with docblock
/**
 * @param string $a First.
 * @param int $b Second.
 * @param bool $c Third.
 */

function multi_params($a, $b, $c): void {}

// Closure parameter
class Container {
	public function bind($abstract, $concrete): void {}
}
