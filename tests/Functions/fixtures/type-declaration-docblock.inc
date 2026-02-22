<?php
declare(strict_types=1);

namespace MyApp;

/**
 * @return string
 */
function returns_string() {
	return 'hello';
}

/**
 * @return integer
 */
function returns_integer() {
	return 42;
}

/**
 * @return boolean
 */
function returns_boolean() {
	return true;
}

/**
 * @return string|null
 */
function returns_nullable() {
	return null;
}

/**
 * @return null|string
 */
function returns_nullable_reversed() {
	return null;
}

/**
 * @return string[]
 */
function returns_array_notation() {
	return [];
}

/**
 * @return array<string>
 */
function returns_generic_array() {
	return [];
}

/**
 * @return ?string
 */
function returns_nullable_shorthand() {
	return null;
}

/**
 * @return string|int
 */
function returns_multi_union() {
	return 'hello';
}

/**
 * @return string|int|bool
 */
function returns_triple_union() {
	return 'hello';
}

/**
 * @return WP_Post
 */
function returns_class_name() {
	return new \stdClass();
}
