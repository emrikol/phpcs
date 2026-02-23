<?php
// Pass-by-reference parameter edge cases for DocblockTypeSync.

// Case 1: Basic reference param with & in docblock — should NOT trigger TypeDrift.
/**
 * Basic reference param.
 *
 * @param string &$value The value.
 *
 * @return void
 */
function basic_ref_with_ampersand( string &$value ): void {
	$value .= ' modified';
}

// Case 2: Basic reference param WITHOUT & in docblock — should NOT trigger TypeDrift.
/**
 * Reference param without ampersand in docblock.
 *
 * @param string $value The value.
 *
 * @return void
 */
function basic_ref_without_ampersand( string &$value ): void {
	$value .= ' modified';
}

// Case 3: Nullable reference param with & in docblock.
/**
 * Nullable reference param.
 *
 * @param int|null &$counter The counter.
 *
 * @return void
 */
function nullable_ref( ?int &$counter ): void {
	if ( null !== $counter ) {
		++$counter;
	}
}

// Case 4: Nullable reference param, docblock uses ? notation with &.
/**
 * Nullable reference param with ? in docblock.
 *
 * @param ?int &$counter The counter.
 *
 * @return void
 */
function nullable_ref_question_mark( ?int &$counter ): void {
	if ( null !== $counter ) {
		++$counter;
	}
}

// Case 5: Multiple params, mixed ref and non-ref, all with & in docblock.
/**
 * Mixed ref and non-ref params.
 *
 * @param string   $name  The name.
 * @param array    &$data The data (by reference).
 * @param int|null &$count The count (by reference).
 *
 * @return void
 */
function mixed_ref_params( string $name, array &$data, ?int &$count ): void {
	$data[] = $name;
	++$count;
}

// Case 6: No type hint but has & in docblock — should produce MissingParamType for type-hinted param only.
// The untyped &$ref param should be skipped by the sniff (no type_hint).
/**
 * Untyped reference param (should be ignored by sniff).
 *
 * @param &$ref Reference param.
 *
 * @return void
 */
function untyped_ref( &$ref ): void {
	$ref = 'changed';
}

// Case 7: Class method with reference params and & in docblock.
class RefMethodClass {

	/**
	 * Method with reference param.
	 *
	 * @param array &$items The items.
	 *
	 * @return int
	 */
	public function process_items( array &$items ): int {
		$items = array_filter( $items );
		return count( $items );
	}

	/**
	 * Static method with reference param.
	 *
	 * @param string &$buffer The buffer.
	 *
	 * @return void
	 */
	public static function append_to_buffer( string &$buffer ): void {
		$buffer .= ' appended';
	}
}

// Case 8: Multiple spaces between type and &$var in docblock.
/**
 * Multiple spaces around reference operator.
 *
 * @param string    &$value The value with extra spaces.
 *
 * @return void
 */
function ref_extra_spaces( string &$value ): void {
	$value .= ' modified';
}

// Case 9: Union type with & in docblock.
/**
 * Union type reference param.
 *
 * @param string|int &$value The value.
 *
 * @return void
 */
function union_type_ref( string|int &$value ): void {
	$value = (string) $value;
}

// Case 10: Callable param (not reference) followed by a reference param.
/**
 * Callable followed by reference param.
 *
 * @param callable $callback The callback.
 * @param array    &$results The results.
 *
 * @return void
 */
function callable_then_ref( callable $callback, array &$results ): void {
	$results[] = $callback();
}

// Case 11: The original SPX bug case — private method with multiple reference params.
class OriginalBugCase {

	/**
	 * Process a single event line from SPX data.
	 *
	 * @param string   $line     Event line to process.
	 * @param callable $attr_time Function to attribute time to stack.
	 * @param array    &$stack   Current function call stack.
	 * @param int|null &$last_ts Last timestamp.
	 *
	 * @return void
	 */
	private function process_event_line( string $line, callable $attr_time, array &$stack, ?int &$last_ts ): void {
	}
}

// Case 12: Reference param with no space between & and $ (uncommon docblock style).
/**
 * No space between ampersand and dollar sign.
 *
 * @param string &$value The value.
 *
 * @return void
 */
function ref_no_space( string &$value ): void {
	$value .= ' modified';
}

// Case 13: Variadic param (not reference) — ensure ... doesn't confuse things.
/**
 * Variadic param.
 *
 * @param string ...$items The items.
 *
 * @return void
 */
function variadic_param( string ...$items ): void {
}

// Case 14: Variadic reference param (PHP allows &...$args).
/**
 * Variadic reference param.
 *
 * @param string &...$items The items.
 *
 * @return void
 */
function variadic_ref_param( string &...$items ): void {
}

// Case 15: Object type with reference.
/**
 * Object type reference param.
 *
 * @param object &$instance The instance.
 *
 * @return void
 */
function object_ref( object &$instance ): void {
}

// Case 16: bool type with reference — less common but valid.
/**
 * Bool reference param.
 *
 * @param bool &$flag The flag.
 *
 * @return void
 */
function bool_ref( bool &$flag ): void {
	$flag = ! $flag;
}

// Case 17: mixed type with reference — rare but valid.
/**
 * Mixed reference param.
 *
 * @param mixed &$data The data.
 *
 * @return void
 */
function mixed_ref( mixed &$data ): void {
	$data = null;
}
