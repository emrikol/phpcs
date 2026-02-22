<?php
// Functions where docblock types contradict code types.

/**
 * Param type drift: code says string, doc says int.
 *
 * @param int $name The name.
 *
 * @return string
 */
function param_drift( string $name ): string {
	return $name;
}

/**
 * Return type drift: code says string, doc says int.
 *
 * @param string $name The name.
 *
 * @return int
 */
function return_drift( string $name ): string {
	return $name;
}

/**
 * Both param and return drift.
 *
 * @param float  $name  The name.
 * @param string $count The count.
 *
 * @return array
 */
function both_drift( string $name, int $count ): bool {
	return strlen( $name ) > $count;
}

/**
 * Nullable drift: code says ?string, doc says int|null.
 *
 * @param int|null $value The value.
 *
 * @return void
 */
function nullable_drift( ?string $value ): void {
}

/**
 * Self vs string drift.
 *
 * @param string $name The name.
 *
 * @return string
 */
function self_drift( string $name ): self {
	return new self();
}

/**
 * Array vs bool drift on return.
 *
 * @return bool
 */
function return_array_drift(): array {
	return array();
}
