<?php
/**
 * Emrikol Coding Standards.
 *
 * Requires a permission_callback in register_rest_route() calls.
 * REST API endpoints without permission callbacks default to public
 * access, which is a security risk.
 *
 * @package Emrikol\Sniffs\WordPress
 */

namespace Emrikol\Sniffs\WordPress;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class RequirePermissionCallbackSniff
 *
 * Detects register_rest_route() calls where the args array does not
 * contain a 'permission_callback' key. WordPress defaults missing
 * permission callbacks to '__return_true' since 5.5, but logs a
 * _doing_it_wrong notice. Explicit callbacks prevent both the
 * security gap and the notice.
 */
class RequirePermissionCallbackSniff implements Sniff {

	/**
	 * Route registration functions to check.
	 *
	 * Comma-separated list configurable via ruleset.xml for custom wrappers.
	 *
	 * @var string
	 */
	public $route_functions = 'register_rest_route';

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_STRING );
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
		$tokens        = $phpcs_file->getTokens();
		$function_name = $tokens[ $stack_ptr ]['content'];

		$route_list = array_map( 'trim', explode( ',', $this->route_functions ) );

		if ( ! in_array( $function_name, $route_list, true ) ) {
			return;
		}

		// Skip method calls ($obj->register_rest_route) and static calls.
		$prev_non_ws = $phpcs_file->findPrevious( Tokens::$emptyTokens, $stack_ptr - 1, null, true );

		if ( false !== $prev_non_ws
			&& ( T_OBJECT_OPERATOR === $tokens[ $prev_non_ws ]['code']
				|| T_DOUBLE_COLON === $tokens[ $prev_non_ws ]['code']
				|| T_NULLSAFE_OBJECT_OPERATOR === $tokens[ $prev_non_ws ]['code'] )
		) {
			return;
		}

		// Verify this is a function call.
		$next_non_ws = $phpcs_file->findNext( Tokens::$emptyTokens, $stack_ptr + 1, null, true );

		if ( false === $next_non_ws || T_OPEN_PARENTHESIS !== $tokens[ $next_non_ws ]['code'] ) {
			return;
		}

		$open_paren  = $next_non_ws;
		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];

		// Collect argument start positions at depth 0.
		$arg_starts = array();
		$depth      = 0;
		$first_tok  = $phpcs_file->findNext( Tokens::$emptyTokens, $open_paren + 1, $close_paren, true );

		if ( false !== $first_tok ) {
			$arg_starts[] = $first_tok;
		}

		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			if ( T_OPEN_PARENTHESIS === $tokens[ $i ]['code']
				|| T_OPEN_SHORT_ARRAY === $tokens[ $i ]['code']
				|| T_OPEN_SQUARE_BRACKET === $tokens[ $i ]['code']
				|| T_OPEN_CURLY_BRACKET === $tokens[ $i ]['code']
			) {
				++$depth;
			} elseif ( T_CLOSE_PARENTHESIS === $tokens[ $i ]['code']
				|| T_CLOSE_SHORT_ARRAY === $tokens[ $i ]['code']
				|| T_CLOSE_SQUARE_BRACKET === $tokens[ $i ]['code']
				|| T_CLOSE_CURLY_BRACKET === $tokens[ $i ]['code']
			) {
				--$depth;
			} elseif ( 0 === $depth && T_COMMA === $tokens[ $i ]['code'] ) {
				$arg_start = $phpcs_file->findNext( Tokens::$emptyTokens, $i + 1, $close_paren, true );
				if ( false !== $arg_start ) {
					$arg_starts[] = $arg_start;
				}
			}
		}

		// register_rest_route( $namespace, $route, $args, $override ).
		// Need at least 3 positional arguments, or a named 'args' argument.
		// Check for named argument 'args'.
		$args_ptr = false;

		foreach ( $arg_starts as $idx => $arg_ptr ) {
			if ( T_PARAM_NAME === $tokens[ $arg_ptr ]['code']
				&& 'args' === $tokens[ $arg_ptr ]['content']
			) {
				$after_label = $phpcs_file->findNext( Tokens::$emptyTokens, $arg_ptr + 1, $close_paren, true );
				if ( false !== $after_label && T_COLON === $tokens[ $after_label ]['code'] ) {
					$args_ptr = $phpcs_file->findNext( Tokens::$emptyTokens, $after_label + 1, $close_paren, true );
				}
				break;
			}

			// Positional: 3rd argument (index 2) is the args array.
			if ( 2 === $idx ) {
				$args_ptr = $arg_ptr;
				break;
			}
		}

		if ( false === $args_ptr ) {
			// No args array found — can't determine permission_callback.
			// If fewer than 3 args, it might be a malformed call; skip.
			return;
		}

		// If the args value is a variable or function call, we can't statically analyze it.
		if ( T_VARIABLE === $tokens[ $args_ptr ]['code']
			|| T_STRING === $tokens[ $args_ptr ]['code']
		) {
			// Could be $args or my_get_args() — skip, can't check statically.
			return;
		}

		// Expect an array. Could be array() or short array [].
		if ( T_ARRAY === $tokens[ $args_ptr ]['code'] ) {
			$arr_open = $phpcs_file->findNext( Tokens::$emptyTokens, $args_ptr + 1, $close_paren, true );
			if ( false === $arr_open || T_OPEN_PARENTHESIS !== $tokens[ $arr_open ]['code'] ) {
				return;
			}
			$arr_close = $tokens[ $arr_open ]['parenthesis_closer'];

			$this->check_args_array( $phpcs_file, $stack_ptr, $arr_open + 1, $arr_close );
			return;
		}

		if ( T_OPEN_SHORT_ARRAY === $tokens[ $args_ptr ]['code'] ) {
			$arr_close = $tokens[ $args_ptr ]['bracket_closer'];

			$this->check_args_array( $phpcs_file, $stack_ptr, $args_ptr + 1, $arr_close );
			return;
		}
	}

	/**
	 * Check an args array for 'permission_callback' key.
	 *
	 * Handles two formats:
	 * 1. Single route: array( 'methods' => 'GET', 'callback' => ..., 'permission_callback' => ... )
	 * 2. Multi-route:  array( array( 'methods' => 'GET', ... ), array( 'methods' => 'POST', ... ) )
	 *
	 * @param File $phpcs_file  The file being scanned.
	 * @param int  $call_ptr    The position of the register_rest_route token (for error reporting).
	 * @param int  $start       Start of array content (after open bracket/paren).
	 * @param int  $end         End of array content (before close bracket/paren).
	 *
	 * @return void
	 */
	private function check_args_array( File $phpcs_file, int $call_ptr, int $start, int $end ): void {
		$tokens = $phpcs_file->getTokens();

		// Detect multi-route format: first element is an array (no string key before it).
		$first_element = $phpcs_file->findNext( Tokens::$emptyTokens, $start, $end, true );

		if ( false === $first_element ) {
			// Empty array.
			$phpcs_file->addError(
				"Missing 'permission_callback' in register_rest_route() args. REST endpoints without explicit permission callbacks are a security risk.",
				$call_ptr,
				'MissingPermissionCallback'
			);
			return;
		}

		// Check if this is multi-route format (first element is a nested array).
		if ( $this->is_array_start( $tokens, $first_element ) ) {
			// Multi-route: check each sub-array.
			$this->check_multi_route( $phpcs_file, $call_ptr, $start, $end );
			return;
		}

		// Single route: look for 'permission_callback' as a key.
		if ( ! $this->has_permission_callback_key( $phpcs_file, $start, $end ) ) {
			$phpcs_file->addError(
				"Missing 'permission_callback' in register_rest_route() args. REST endpoints without explicit permission callbacks are a security risk.",
				$call_ptr,
				'MissingPermissionCallback'
			);
		}
	}

	/**
	 * Check multi-route format where args is an array of route arrays.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $call_ptr   The position of register_rest_route token.
	 * @param int  $start      Start of outer array content.
	 * @param int  $end        End of outer array content.
	 *
	 * @return void
	 */
	private function check_multi_route( File $phpcs_file, int $call_ptr, int $start, int $end ): void {
		$tokens = $phpcs_file->getTokens();
		$i      = $start;

		while ( $i < $end ) {
			$tok = $phpcs_file->findNext( Tokens::$emptyTokens, $i, $end, true );

			if ( false === $tok ) {
				break;
			}

			if ( T_ARRAY === $tokens[ $tok ]['code'] ) {
				$inner_open = $phpcs_file->findNext( Tokens::$emptyTokens, $tok + 1, $end, true );
				if ( false !== $inner_open && T_OPEN_PARENTHESIS === $tokens[ $inner_open ]['code'] ) {
					$inner_close = $tokens[ $inner_open ]['parenthesis_closer'];
					if ( ! $this->has_permission_callback_key( $phpcs_file, $inner_open + 1, $inner_close ) ) {
						$phpcs_file->addError(
							"Missing 'permission_callback' in register_rest_route() args. REST endpoints without explicit permission callbacks are a security risk.",
							$tok,
							'MissingPermissionCallback'
						);
					}
					$i = $inner_close + 1;
					continue;
				}
			} elseif ( T_OPEN_SHORT_ARRAY === $tokens[ $tok ]['code'] ) {
				$inner_close = $tokens[ $tok ]['bracket_closer'];
				if ( ! $this->has_permission_callback_key( $phpcs_file, $tok + 1, $inner_close ) ) {
					$phpcs_file->addError(
						"Missing 'permission_callback' in register_rest_route() args. REST endpoints without explicit permission callbacks are a security risk.",
						$tok,
						'MissingPermissionCallback'
					);
				}
				$i = $inner_close + 1;
				continue;
			}

			++$i;
		}
	}

	/**
	 * Check if 'permission_callback' exists as a key in an array section.
	 *
	 * Scans at depth 0 for string literal 'permission_callback' followed by T_DOUBLE_ARROW.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $start      Start position (after opening bracket/paren).
	 * @param int  $end        End position (closing bracket/paren).
	 *
	 * @return bool True if permission_callback key is found.
	 */
	private function has_permission_callback_key( File $phpcs_file, int $start, int $end ): bool {
		$tokens = $phpcs_file->getTokens();
		$depth  = 0;

		for ( $i = $start; $i < $end; $i++ ) {
			$code = $tokens[ $i ]['code'];

			if ( T_OPEN_PARENTHESIS === $code
				|| T_OPEN_SHORT_ARRAY === $code
				|| T_OPEN_SQUARE_BRACKET === $code
				|| T_OPEN_CURLY_BRACKET === $code
			) {
				++$depth;
				continue;
			}

			if ( T_CLOSE_PARENTHESIS === $code
				|| T_CLOSE_SHORT_ARRAY === $code
				|| T_CLOSE_SQUARE_BRACKET === $code
				|| T_CLOSE_CURLY_BRACKET === $code
			) {
				--$depth;
				continue;
			}

			// Only check at depth 0 (top-level keys of this array).
			if ( 0 !== $depth ) {
				continue;
			}

			if ( T_CONSTANT_ENCAPSED_STRING !== $code ) {
				continue;
			}

			$key_value = trim( $tokens[ $i ]['content'], "\"'" );

			if ( 'permission_callback' === $key_value ) {
				// Verify it's followed by a double arrow (it's a key, not a value).
				$next = $phpcs_file->findNext( Tokens::$emptyTokens, $i + 1, $end, true );
				if ( false !== $next && T_DOUBLE_ARROW === $tokens[ $next ]['code'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a token starts an array literal.
	 *
	 * @param array $tokens Token array.
	 * @param int   $ptr    Token position.
	 *
	 * @return bool True if the token is array() or [].
	 */
	private function is_array_start( array $tokens, int $ptr ): bool {
		return T_ARRAY === $tokens[ $ptr ]['code'] || T_OPEN_SHORT_ARRAY === $tokens[ $ptr ]['code'];
	}
}
