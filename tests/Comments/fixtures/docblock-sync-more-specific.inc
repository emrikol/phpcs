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
