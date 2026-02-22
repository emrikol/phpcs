<?php
// Functions where the docblock exists but @param/@return tags are missing.

/**
 * Missing @param tag for a typed parameter.
 *
 * @return string
 */
function missing_param_tag( string $name ): string {
	return $name;
}

/**
 * Missing @return tag with a typed return.
 *
 * @param string $name The name.
 */
function missing_return_tag( string $name ): string {
	return $name;
}

/**
 * Missing both @param and @return tags.
 */
function missing_both( string $name, int $count ): bool {
	return strlen( $name ) > $count;
}

/**
 * Has one @param but missing the second.
 *
 * @param string $name The name.
 */
function missing_second_param( string $name, int $count ): void {
}

/**
 * Missing @return with existing @param tags.
 *
 * @param string $a First param.
 * @param int    $b Second param.
 */
function missing_return_with_params( string $a, int $b ): string {
	return $a . $b;
}

/**
 * Missing @param for nullable typed parameter.
 *
 * @return void
 */
function missing_nullable_param( ?string $value ): void {
}
