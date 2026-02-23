<?php
// Tests for comments between docblock and function declaration.

// === Case 1: phpcs:ignore between docblock and function ===
// The docblock should still be found. No MissingDocblock error.

/**
 * Function with phpcs:ignore between docblock and declaration.
 *
 * @param string $name The name.
 *
 * @return void
 */
// phpcs:ignore Some.Other.Sniff -- reason here.
function ignore_between_docblock_and_func( string $name ): void {
}

// === Case 2: phpcs:ignore on same line as function (inline) ===
// The docblock should be found. No MissingDocblock error.

/**
 * Function with inline phpcs:ignore.
 *
 * @param string $name The name.
 *
 * @return void
 */
function inline_ignore( string $name ): void { // phpcs:ignore Some.Other.Sniff
}

// === Case 3: phpcs:disable between docblock and function ===
// The docblock should still be found.

/**
 * Function with phpcs:disable between docblock and declaration.
 *
 * @param int $count The count.
 *
 * @return int
 */
// phpcs:disable Some.Other.Sniff
function disable_between( int $count ): int {
	return $count;
}
// phpcs:enable Some.Other.Sniff

// === Case 4: Regular comment between docblock and function ===
// A non-phpcs comment between docblock and function means the
// docblock does NOT belong to this function. MissingDocblock expected.

/**
 * This docblock is for something else entirely.
 *
 * @param string $value The value.
 *
 * @return void
 */
// This is a regular comment, not a phpcs directive.
function regular_comment_between( string $value ): void {
}

// === Case 5: Multiple phpcs:ignore comments stacked ===
// Docblock should still be found through multiple directive comments.

/**
 * Function with multiple ignore comments.
 *
 * @param string $name The name.
 * @param int    $age  The age.
 *
 * @return string
 */
// phpcs:ignore First.Sniff.Code
// phpcs:ignore Second.Sniff.Code
function multiple_ignores( string $name, int $age ): string {
	return $name;
}

// === Case 6: phpcs:ignore between modifier and function keyword ===

class CommentBetweenClass {

	/**
	 * Method with phpcs:ignore between docblock and declaration.
	 *
	 * @param string $value The value.
	 *
	 * @return int
	 */
	// phpcs:ignore Some.Sniff.Code
	public function ignore_before_method( string $value ): int {
		return strlen( $value );
	}

	/**
	 * Static method with phpcs:ignore.
	 *
	 * @param array $items The items.
	 *
	 * @return int
	 */
	// phpcs:ignore Some.Sniff.Code
	public static function ignore_before_static( array $items ): int {
		return count( $items );
	}
}

// === Case 7: No docblock, just phpcs:ignore before function ===
// Should generate a docblock BEFORE the phpcs:ignore comment.

// phpcs:ignore Some.Other.Sniff
function no_docblock_with_ignore( string $name ): void {
}

// === Case 8: Complete docblock with phpcs:ignore between ===
// Fixer must NOT create a second docblock.

/**
 * This function already has a complete docblock.
 *
 * @param string $value The value.
 *
 * @return string
 */
// phpcs:ignore Some.Other.Sniff -- intentional suppression.
function already_has_docblock( string $value ): string {
	return $value;
}

// === Case 9: Incomplete docblock with phpcs:ignore between ===
// Should fix the EXISTING docblock (add missing tags), not create a new one.

class FixExistingWithIgnore {

	/**
	 * Method description.
	 */
	// phpcs:ignore Some.Sniff.Code
	public function incomplete_docblock( string $name, int $count ): bool {
		return strlen( $name ) > $count;
	}
}

// === Case 10: Block comment (not doc comment) between docblock and function ===
// Non-doc block comments should still break association. MissingDocblock expected.

/**
 * Function with block comment between.
 *
 * @param string $name The name.
 *
 * @return void
 */
/* This is a non-doc block comment */
function block_comment_between( string $name ): void {
}

// === Case 11: phpcs:ignore for THIS sniff should suppress ===
// phpcs:ignore Emrikol.Comments.DocblockTypeSync.MissingDocblock
function suppressed_no_docblock( string $name ): void {
}

// === Case 12: Existing docblock but missing tags, with phpcs:ignore between ===
// Should get MissingParamTag and MissingReturnTag, NOT MissingDocblock.

/**
 * Process the data.
 */
// phpcs:ignore Some.Other.Sniff
function existing_but_missing_tags( string $input ): string {
	return $input;
}

// === Case 13: phpcs:enable between docblock and function ===
// phpcs:enable is also a directive comment, should allow docblock through.

// phpcs:disable Some.Other.Sniff
/**
 * Function with phpcs:enable between.
 *
 * @param int $value The value.
 *
 * @return int
 */
// phpcs:enable Some.Other.Sniff
function enable_between( int $value ): int {
	return $value;
}

// === Case 14: phpcs:ignore followed by regular comment ===
// The regular comment after the directive should break the association.
// MissingDocblock expected.

/**
 * Docblock before mixed comments (directive then regular).
 *
 * @param string $name The name.
 *
 * @return void
 */
// phpcs:ignore Some.Sniff
// This regular comment breaks the chain.
function directive_then_regular( string $name ): void {
}

// === Case 15: Regular comment followed by phpcs:ignore ===
// The regular comment before the directive should break the association.
// MissingDocblock expected.

/**
 * Docblock before reversed mixed comments.
 *
 * @param string $name The name.
 *
 * @return void
 */
// This regular comment breaks the chain.
// phpcs:ignore Some.Sniff
function regular_then_directive( string $name ): void {
}

// === Case 16: Blank line between phpcs:ignore and function ===
// Blank lines are T_WHITESPACE, should still be transparent.

/**
 * Docblock with blank line after directive.
 *
 * @param string $value The value.
 *
 * @return void
 */
// phpcs:ignore Some.Other.Sniff

function blank_line_after_ignore( string $value ): void {
}

// === Case 17: Interface method with phpcs:ignore between ===

interface DirectiveInterface {

	/**
	 * Interface method with phpcs:ignore.
	 *
	 * @param string $value The value.
	 *
	 * @return int
	 */
	// phpcs:ignore Some.Sniff.Code
	public function interface_method( string $value ): int;
}

// === Case 18: Hash-style phpcs comment ===
// PHP # comments are T_COMMENT. PHPCS recognizes # phpcs:ignore as directive.

/**
 * Function with hash-style phpcs ignore.
 *
 * @param string $name The name.
 *
 * @return void
 */
# phpcs:ignore Some.Other.Sniff
function hash_ignore_between( string $name ): void {
}

// === Case 19: Untyped function with phpcs:ignore — no errors at all ===
// No typed params and no return type means nothing for DocblockTypeSync to check.

// phpcs:ignore Some.Other.Sniff
function untyped_with_ignore( $name ) {
}

// === Case 20: Abstract method with phpcs:ignore between docblock and declaration ===

abstract class DirectiveAbstractClass {

	/**
	 * Abstract method with phpcs:ignore before it.
	 *
	 * @param string $value The value.
	 *
	 * @return int
	 */
	// phpcs:ignore Some.Sniff.Code
	abstract public function abstract_with_ignore( string $value ): int;
}

// === Case 21: Mixed phpcs:ignore and phpcs:disable stacked ===
// Multiple different directive types should all be transparent.

/**
 * Function with mixed directive types.
 *
 * @param string $name The name.
 *
 * @return string
 */
// phpcs:ignore Some.Sniff
// phpcs:disable Another.Sniff
function mixed_directives_between( string $name ): string {
	return $name;
}
// phpcs:enable Another.Sniff

// === Case 22: Empty docblock with phpcs:ignore between ===
// Empty docblock should still be found. MissingParamTag + MissingReturnTag expected.

/**
 */
// phpcs:ignore Some.Sniff
function empty_docblock_with_ignore( string $name ): string {
	return $name;
}

// === Case 23: Fixer — no docblock with phpcs:ignore should insert BEFORE directive ===
// Same as Case 7, but validates fixer output placement.

// phpcs:ignore Some.Sniff.ToKeep
function fixer_insert_before_ignore( string $value, int $count ): bool {
	return strlen( $value ) > $count;
}
