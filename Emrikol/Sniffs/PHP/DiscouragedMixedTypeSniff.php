<?php
/**
 * Emrikol Coding Standards.
 *
 * Discourages the use of the 'mixed' type in function parameters,
 * return types, and property declarations.
 *
 * @package Emrikol\Sniffs\PHP
 */

namespace Emrikol\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class DiscouragedMixedTypeSniff
 *
 * Warns when 'mixed' is used as a type declaration. Since 'mixed' is
 * sometimes necessary for third-party code compatibility, this produces
 * warnings (not errors) that can be suppressed with phpcs:ignore.
 */
class DiscouragedMixedTypeSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_FUNCTION, T_CLOSURE, T_FN, T_VARIABLE );
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
		$tokens = $phpcs_file->getTokens();

		if ( T_VARIABLE === $tokens[ $stack_ptr ]['code'] ) {
			$this->check_property( $phpcs_file, $stack_ptr );
			return;
		}

		$this->check_parameters( $phpcs_file, $stack_ptr );
		$this->check_return_type( $phpcs_file, $stack_ptr );
	}

	/**
	 * Check function/method parameters for mixed type usage.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the function token.
	 *
	 * @return void
	 */
	private function check_parameters( File $phpcs_file, int $stack_ptr ): void {
		$parameters = $phpcs_file->getMethodParameters( $stack_ptr );

		foreach ( $parameters as $param ) {
			if ( 'mixed' === $param['type_hint'] ) {
				$phpcs_file->addWarning(
					"Parameter '%s' uses the 'mixed' type. Consider using a more specific type.",
					$stack_ptr,
					'MixedParameterType',
					array( $param['name'] )
				);
			}
		}
	}

	/**
	 * Check function/method return type for mixed type usage.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the function token.
	 *
	 * @return void
	 */
	private function check_return_type( File $phpcs_file, int $stack_ptr ): void {
		$tokens = $phpcs_file->getTokens();

		if ( ! isset( $tokens[ $stack_ptr ]['parenthesis_closer'] ) ) {
			return;
		}

		$closer = $tokens[ $stack_ptr ]['parenthesis_closer'];

		// Determine the search boundary for the return type colon.
		// For closures with use() clauses, the colon is beyond the use clause.
		if ( isset( $tokens[ $stack_ptr ]['scope_opener'] ) ) {
			$search_end = $tokens[ $stack_ptr ]['scope_opener'];
		} else {
			// Abstract/interface methods have no body — search until semicolon.
			$search_end = $phpcs_file->findNext( T_SEMICOLON, $closer + 1 );
			if ( false === $search_end ) {
				return;
			}
		}

		// Find the return type colon between the close paren and the body.
		$colon = $phpcs_file->findNext( T_COLON, $closer + 1, $search_end );

		if ( false === $colon ) {
			return;
		}

		// Build the return type string from tokens after the colon, skipping
		// whitespace and comments.
		$return_type = '';
		$next        = $colon + 1;
		$end_tokens  = array( T_OPEN_CURLY_BRACKET, T_SEMICOLON, T_CLOSE_PARENTHESIS, T_FN_ARROW );

		while ( isset( $tokens[ $next ] ) && ! in_array( $tokens[ $next ]['code'], $end_tokens, true ) ) {
			if ( ! isset( Tokens::$emptyTokens[ $tokens[ $next ]['code'] ] ) ) {
				$return_type .= $tokens[ $next ]['content'];
			}
			++$next;
		}

		if ( 'mixed' === $return_type ) {
			$phpcs_file->addWarning(
				"Return type uses 'mixed'. Consider using a more specific type.",
				$stack_ptr,
				'MixedReturnType'
			);
		}
	}

	/**
	 * Check property declarations for mixed type usage.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the variable token.
	 *
	 * @return void
	 */
	private function check_property( File $phpcs_file, int $stack_ptr ): void {
		// Check if this variable is a PHP 8.4 property hook set parameter.
		// set(mixed $value) uses T_STRING "set", not T_FUNCTION, so
		// getMethodParameters() won't find it via the normal path.
		$this->check_hook_set_param( $phpcs_file, $stack_ptr );

		try {
			$member_props = $phpcs_file->getMemberProperties( $stack_ptr );
		} catch ( \RuntimeException $e ) {
			return;
		}

		if ( 'mixed' === $member_props['type'] ) {
			$tokens = $phpcs_file->getTokens();
			$name   = $tokens[ $stack_ptr ]['content'];

			$phpcs_file->addWarning(
				"Property '%s' uses the 'mixed' type. Consider using a more specific type.",
				$stack_ptr,
				'MixedPropertyType',
				array( $name )
			);
		}
	}

	/**
	 * Check if a variable is inside a PHP 8.4 property hook set() parameter
	 * list with a mixed type hint.
	 *
	 * Property hook set parameters use T_STRING "set" rather than T_FUNCTION,
	 * so getMethodParameters() doesn't handle them. We manually detect:
	 *   set ( mixed $variable )
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the variable token.
	 *
	 * @return void
	 */
	private function check_hook_set_param( File $phpcs_file, int $stack_ptr ): void {
		$tokens = $phpcs_file->getTokens();

		// Must be inside parentheses.
		if ( empty( $tokens[ $stack_ptr ]['nested_parenthesis'] ) ) {
			return;
		}

		// Get the innermost open paren.
		$parens     = $tokens[ $stack_ptr ]['nested_parenthesis'];
		$open_paren = array_key_last( $parens );

		// The token before the open paren (skipping whitespace) should be T_STRING "set".
		$before_paren = $phpcs_file->findPrevious( Tokens::$emptyTokens, $open_paren - 1, null, true );

		if ( false === $before_paren
			|| T_STRING !== $tokens[ $before_paren ]['code']
			|| 'set' !== $tokens[ $before_paren ]['content']
		) {
			return;
		}

		// Scan backwards from $stack_ptr to the open paren, collecting type tokens.
		$type = '';

		for ( $i = $stack_ptr - 1; $i > $open_paren; $i-- ) {
			if ( isset( Tokens::$emptyTokens[ $tokens[ $i ]['code'] ] ) ) {
				continue;
			}

			// Type tokens: T_STRING (for named types like mixed, string, etc.).
			if ( T_STRING === $tokens[ $i ]['code'] ) {
				$type = $tokens[ $i ]['content'];
				break;
			}

			// Stop on any non-type token.
			break;
		}

		if ( 'mixed' === $type ) {
			$phpcs_file->addWarning(
				"Parameter '%s' uses the 'mixed' type. Consider using a more specific type.",
				$stack_ptr,
				'MixedParameterType',
				array( $tokens[ $stack_ptr ]['content'] )
			);
		}
	}
}
