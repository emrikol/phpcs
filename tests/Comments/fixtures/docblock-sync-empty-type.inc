<?php
// Functions where @param/@return tags exist but lack types.

/**
 * Param tag exists but has no type.
 *
 * @param $name The name.
 *
 * @return string
 */
function empty_param_type( string $name ): string {
	return $name;
}

/**
 * Return tag exists but has no type.
 *
 * @param string $name The name.
 *
 * @return
 */
function empty_return_type( string $name ): string {
	return $name;
}

/**
 * Both param and return lack types.
 *
 * @param $name The name.
 * @param $count The count.
 *
 * @return
 */
function both_empty( string $name, int $count ): bool {
	return strlen( $name ) > $count;
}

/**
 * Nullable param with empty type on the tag.
 *
 * @param $value The value.
 *
 * @return void
 */
function nullable_empty_type( ?string $value ): void {
}

/**
 * Param with extra whitespace around the variable name.
 *
 * @param   $data   The data.
 *
 * @return void
 */
function extra_whitespace_param( string $data ): void {
}
