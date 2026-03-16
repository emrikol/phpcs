<?php
// Functions where docblock types are valid specializations of code types.
// No errors or warnings should be emitted.

/**
 * Array specialized to string[].
 *
 * @param string[] $items The items.
 *
 * @return void
 */
function array_to_typed_array( array $items ): void {
}

/**
 * Array specialized to array<string, int>.
 *
 * @param array<string, int> $map The map.
 *
 * @return void
 */
function array_to_generic( array $map ): void {
}

/**
 * Array specialized to WP_Post[].
 *
 * @param \WP_Post[] $posts The posts.
 *
 * @return void
 */
function array_to_class_array( array $posts ): void {
}

/**
 * Object specialized to WP_Post.
 *
 * @param \WP_Post $post The post.
 *
 * @return void
 */
function object_to_class( object $post ): void {
}

/**
 * Callable specialized to Closure.
 *
 * @param \Closure $callback The callback.
 *
 * @return void
 */
function callable_to_closure( callable $callback ): void {
}

/**
 * Callable specialized to callable(string): int.
 *
 * @param callable(string):int $callback The callback.
 *
 * @return void
 */
function callable_to_typed_callable( callable $callback ): void {
}

/**
 * Iterable specialized to iterable<string>.
 *
 * @param iterable<string> $items The items.
 *
 * @return void
 */
function iterable_to_generic( iterable $items ): void {
}

/**
 * Nullable array specialized to string[]|null.
 *
 * @param string[]|null $items The items.
 *
 * @return void
 */
function nullable_array_specialized( ?array $items ): void {
}

/**
 * Return type specialization: array to int[].
 *
 * @return int[]
 */
function return_specialized(): array {
	return array( 1, 2, 3 );
}

// ===== PHPStan array shapes =====

/**
 * Array shape specialization.
 *
 * @return array{success: bool, data: array{message: string}}
 */
function return_array_shape(): array {
	return array( 'success' => true, 'data' => array( 'message' => 'ok' ) );
}

/**
 * Simple array shape specialization.
 *
 * @return array{success: bool, message: string}
 */
function return_simple_shape(): array {
	return array( 'success' => true, 'message' => 'ok' );
}

/**
 * Array shape param specialization.
 *
 * @param array{success: bool, data: array{message: string}} $result The result.
 *
 * @return void
 */
function param_array_shape( array $result ): void {
}

// ===== PHPStan generics in union types =====

/**
 * Generic array in union with class.
 *
 * @return array<int, array<string, mixed>>|\WP_Error
 */
function return_generic_union(): array|\WP_Error {
	return array();
}

/**
 * Simple generic array in union with class.
 *
 * @return array<string, mixed>|\WP_Error
 */
function return_simple_generic_union(): array|\WP_Error {
	return array();
}

// ===== PHPStan pseudo-types =====

/**
 * List specialization.
 *
 * @param list<string> $items The items.
 *
 * @return void
 */
function param_list( array $items ): void {
}

/**
 * Non-empty-array specialization.
 *
 * @param non-empty-array<string> $items The items.
 *
 * @return void
 */
function param_non_empty_array( array $items ): void {
}

/**
 * Non-empty-list specialization.
 *
 * @param non-empty-list<int> $items The items.
 *
 * @return void
 */
function param_non_empty_list( array $items ): void {
}

// ===== Bracket-aware union splitting =====

/**
 * Array shape with union inside braces — pipe inside brackets must not split.
 *
 * @param array{a: string|int, b: bool} $data The data.
 *
 * @return void
 */
function param_shape_with_inner_union( array $data ): void {
}

/**
 * Nullable array shape.
 *
 * @param array{id: int, name: string}|null $record The record.
 *
 * @return void
 */
function param_nullable_shape( ?array $record ): void {
}
