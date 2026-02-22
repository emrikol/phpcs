<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that all class properties have type declarations.
 *
 * @package Emrikol\Sniffs\Classes
 */

namespace Emrikol\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class TypedPropertySniff
 *
 * Checks that class properties have type declarations.
 * Constructor promotion parameters are skipped (handled by TypeHintingSniff).
 */
class TypedPropertySniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_VARIABLE );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ): void {
		// getMemberProperties() throws RuntimeException for non-property variables
		// (local vars, constructor promotion params, etc.).
		try {
			$member_props = $phpcs_file->getMemberProperties( $stack_ptr );
		} catch ( \RuntimeException $e ) {
			return;
		}

		// PHP 8.4 property hooks: PHPCS doesn't create scope entries for hook
		// body braces, so variables inside hook bodies (e.g. $this, $value)
		// appear as class-level and pass getMemberProperties() checks.
		// Detect and skip them by looking for enclosing orphan braces.
		if ( $this->is_inside_property_hook( $phpcs_file, $stack_ptr ) ) {
			return;
		}

		if ( '' === $member_props['type'] ) {
			$tokens = $phpcs_file->getTokens();
			$name   = $tokens[ $stack_ptr ]['content'];

			$phpcs_file->addError(
				"Missing type declaration for property '%s'.",
				$stack_ptr,
				'MissingPropertyType',
				array( $name )
			);
		}
	}

	/**
	 * Checks if a variable is inside a PHP 8.4 property hook body.
	 *
	 * PHPCS 3.13.x doesn't create scope entries for property hook braces,
	 * so we detect them by walking backwards and finding curly braces
	 * that lack scope_condition (orphan braces).
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the variable token.
	 *
	 * @return bool True if the variable is inside a property hook body.
	 */
	private function is_inside_property_hook( File $phpcs_file, int $stack_ptr ): bool {
		$tokens = $phpcs_file->getTokens();

		// Find the innermost class/trait/anon-class scope opener.
		$conditions = $tokens[ $stack_ptr ]['conditions'];

		if ( empty( $conditions ) ) {
			return false;
		}

		$class_ptr = array_key_last( $conditions );

		if ( ! isset( $tokens[ $class_ptr ]['scope_opener'] ) ) {
			return false;
		}

		$class_opener = $tokens[ $class_ptr ]['scope_opener'];

		// Walk backwards from the variable, tracking brace depth.
		// If we encounter an unmatched { without scope_condition at depth 0,
		// we're inside a property hook body (or similar unrecognized scope).
		$depth = 0;

		for ( $i = $stack_ptr - 1; $i > $class_opener; $i-- ) {
			$code = $tokens[ $i ]['code'];

			if ( T_CLOSE_CURLY_BRACKET === $code ) {
				if ( array_key_exists( 'scope_condition', $tokens[ $i ] ) ) {
					// Recognized scope closer — skip to its opener.
					$i = $tokens[ $i ]['scope_opener'];
					continue;
				}

				++$depth;
			} elseif ( T_OPEN_CURLY_BRACKET === $code ) {
				if ( array_key_exists( 'scope_condition', $tokens[ $i ] ) ) {
					// Recognized scope opener (class body, method, etc.) — not in hook.
					return false;
				}

				if ( $depth > 0 ) {
					--$depth;
				} else {
					// Unmatched orphan { at our level — property hook body.
					return true;
				}
			}
		}

		return false;
	}
}
