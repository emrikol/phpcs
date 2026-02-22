<?php
declare(strict_types=1);

namespace MyApp;

/**
 * @param string $name The name.
 */
function greet($name): string {
	return "Hello, $name";
}

/**
 * @param integer $count The count.
 */
function count_items($count): void {}

/**
 * @param boolean $flag The flag.
 */

function toggle($flag): void {}

/**
 * @param string|null $value Optional value.
 */

function nullable_param($value): void {}

/**
 * @param null|string $value Optional value.
 */

function nullable_param_reversed($value): void {}

/**
 * @param string[] $items List of strings.
 */

function with_array($items): void {}

/**
 * @param array<string> $items Generic array.
 */

function with_generic_array($items): void {}

/**
 * @param string|int $value Multi-type union.
 */

function multi_union($value): void {}

/**
 * @param WP_Post $post A post object.
 */

function with_class_name($post): void {}
