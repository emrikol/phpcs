<?php
/**
 * Emrikol Coding Standards.
 *
 * Forbids closures and arrow functions as WordPress hook callbacks.
 * Closures passed to add_action()/add_filter() can't be unhooked
 * with remove_action()/remove_filter().
 *
 * @package Emrikol\Sniffs\WordPress
 */

namespace Emrikol\Sniffs\WordPress;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class NoHookClosureSniff
 *
 * Detects closures and arrow functions used as callbacks in
 * add_action() and add_filter() calls. These break WordPress
 * extensibility because they cannot be unhooked.
 */
class NoHookClosureSniff implements Sniff {

	/**
	 * Hook registration functions to check.
	 *
	 * Comma-separated list configurable via ruleset.xml for custom wrappers.
	 *
	 * @var string
	 */
	public $hook_functions = 'add_action,add_filter';

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

		// Check if the token is one of the hook functions.
		$hook_list = array_map( 'trim', explode( ',', $this->hook_functions ) );

		if ( ! in_array( $function_name, $hook_list, true ) ) {
			// Check for indirect hook registration via call_user_func / call_user_func_array.
			if ( 'call_user_func' === $function_name || 'call_user_func_array' === $function_name ) {
				$this->check_indirect_registration( $phpcs_file, $stack_ptr, $hook_list );
			}
			return;
		}

		// Skip method calls ($obj->add_action) and static calls (MyClass::add_action).
		$prev_non_ws = $phpcs_file->findPrevious( Tokens::$emptyTokens, $stack_ptr - 1, null, true );

		if ( false !== $prev_non_ws
			&& ( T_OBJECT_OPERATOR === $tokens[ $prev_non_ws ]['code']
				|| T_DOUBLE_COLON === $tokens[ $prev_non_ws ]['code']
				|| T_NULLSAFE_OBJECT_OPERATOR === $tokens[ $prev_non_ws ]['code'] )
		) {
			return;
		}

		// Verify this is a function call: next non-whitespace/comment token should be T_OPEN_PARENTHESIS.
		$next_non_ws = $phpcs_file->findNext( Tokens::$emptyTokens, $stack_ptr + 1, null, true );

		if ( false === $next_non_ws || T_OPEN_PARENTHESIS !== $tokens[ $next_non_ws ]['code'] ) {
			return;
		}

		$open_paren  = $next_non_ws;
		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];

		// Collect all argument start positions at depth 0.
		$arg_starts = array();
		$depth      = 0;

		// First argument starts right after the open paren.
		$first_token = $phpcs_file->findNext( Tokens::$emptyTokens, $open_paren + 1, $close_paren, true );

		if ( false !== $first_token ) {
			$arg_starts[] = $first_token;
		}

		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			if ( T_OPEN_PARENTHESIS === $tokens[ $i ]['code']
				|| T_OPEN_SHORT_ARRAY === $tokens[ $i ]['code']
				|| T_OPEN_SQUARE_BRACKET === $tokens[ $i ]['code']
			) {
				++$depth;
			} elseif ( T_CLOSE_PARENTHESIS === $tokens[ $i ]['code']
				|| T_CLOSE_SHORT_ARRAY === $tokens[ $i ]['code']
				|| T_CLOSE_SQUARE_BRACKET === $tokens[ $i ]['code']
			) {
				--$depth;
			} elseif ( 0 === $depth && T_COMMA === $tokens[ $i ]['code'] ) {
				$arg_start = $phpcs_file->findNext( Tokens::$emptyTokens, $i + 1, $close_paren, true );
				if ( false !== $arg_start ) {
					$arg_starts[] = $arg_start;
				}
			}
		}

		// Need at least 2 arguments (hook name + callback).
		if ( count( $arg_starts ) < 2 ) {
			return;
		}

		// Check the callback argument for closures.
		// With positional args, the callback is the 2nd argument (index 1).
		// With named args, any argument could be the callback, so check all named args.
		foreach ( $arg_starts as $idx => $arg_ptr ) {
			$value_ptr = $arg_ptr;
			$is_named  = ( T_PARAM_NAME === $tokens[ $arg_ptr ]['code'] );

			// Skip named argument labels (T_PARAM_NAME + T_COLON) to find the value.
			if ( $is_named ) {
				$after_label = $phpcs_file->findNext( Tokens::$emptyTokens, $arg_ptr + 1, $close_paren, true );
				if ( false !== $after_label && T_COLON === $tokens[ $after_label ]['code'] ) {
					$value_ptr = $phpcs_file->findNext( Tokens::$emptyTokens, $after_label + 1, $close_paren, true );
					if ( false === $value_ptr ) {
						continue;
					}
				}
			}

			// For positional args, only check the 2nd argument (the callback position).
			// For named args, check all of them (any could be the callback).
			if ( ! $is_named && 1 !== $idx ) {
				continue;
			}

			// Skip past `static` keyword to find closure/arrow function.
			// `static function() {}` and `static fn() =>` are still closures.
			if ( T_STATIC === $tokens[ $value_ptr ]['code'] ) {
				$after_static = $phpcs_file->findNext( Tokens::$emptyTokens, $value_ptr + 1, $close_paren, true );
				if ( false !== $after_static ) {
					$value_ptr = $after_static;
				}
			}

			// Check if the argument value is a closure or arrow function.
			if ( T_CLOSURE === $tokens[ $value_ptr ]['code'] || T_FN === $tokens[ $value_ptr ]['code'] ) {
				$phpcs_file->addError(
					"Closure used as callback for '%s'. Use a named function or method reference instead — closures cannot be unhooked.",
					$value_ptr,
					'ClosureHookCallback',
					array( $function_name )
				);
				continue;
			}

			// Check for Closure::fromCallable() / Closure::bind() wrappers.
			// These produce closure objects that can't be unhooked either.
			$class_ptr = $value_ptr;

			// Handle leading namespace separator: \Closure::fromCallable(...).
			if ( T_NS_SEPARATOR === $tokens[ $class_ptr ]['code'] ) {
				$class_ptr = $phpcs_file->findNext( Tokens::$emptyTokens, $class_ptr + 1, $close_paren, true );
				if ( false === $class_ptr ) {
					continue;
				}
			}

			if ( T_STRING === $tokens[ $class_ptr ]['code']
				&& 'Closure' === $tokens[ $class_ptr ]['content']
			) {
				$dbl_colon = $phpcs_file->findNext( Tokens::$emptyTokens, $class_ptr + 1, $close_paren, true );
				if ( false !== $dbl_colon && T_DOUBLE_COLON === $tokens[ $dbl_colon ]['code'] ) {
					$method_ptr = $phpcs_file->findNext( Tokens::$emptyTokens, $dbl_colon + 1, $close_paren, true );
					if ( false !== $method_ptr
						&& T_STRING === $tokens[ $method_ptr ]['code']
						&& in_array( $tokens[ $method_ptr ]['content'], array( 'fromCallable', 'bind' ), true )
					) {
						$phpcs_file->addError(
							"'Closure::%s()' used as callback for '%s'. The resulting closure cannot be unhooked.",
							$value_ptr,
							'ClosureWrapperCallback',
							array( $tokens[ $method_ptr ]['content'], $function_name )
						);
						continue;
					}
				}
			}

			// Check for anonymous class instances: new class { ... }.
			// These can't be unhooked because there's no way to reference
			// the same anonymous class instance later.
			if ( T_NEW === $tokens[ $value_ptr ]['code'] ) {
				$after_new = $phpcs_file->findNext( Tokens::$emptyTokens, $value_ptr + 1, $close_paren, true );
				if ( false !== $after_new && T_ANON_CLASS === $tokens[ $after_new ]['code'] ) {
					$phpcs_file->addError(
						"Anonymous class used as callback for '%s'. Use a named class or function reference instead — anonymous instances cannot be unhooked.",
						$value_ptr,
						'AnonymousClassCallback',
						array( $function_name )
					);
					continue;
				}
			}

			// Check for first-class callable syntax: func(...), Class::method(...), $obj->method(...).
			// These create Closure objects that cannot be unhooked.
			if ( $this->is_first_class_callable( $phpcs_file, $value_ptr, $close_paren ) ) {
				$phpcs_file->addError(
					"First-class callable used as callback for '%s'. Use a named function string or array reference instead — first-class callables create closures that cannot be unhooked.",
					$value_ptr,
					'FirstClassCallableCallback',
					array( $function_name )
				);
				continue;
			}

			// Check for variable callback — needs manual inspection.
			// Only warn when the variable IS the entire callback (not part of a method call).
			if ( T_VARIABLE === $tokens[ $value_ptr ]['code'] ) {
				$after_var = $phpcs_file->findNext( Tokens::$emptyTokens, $value_ptr + 1, $close_paren + 1, true );
				if ( false !== $after_var
					&& ( T_COMMA === $tokens[ $after_var ]['code']
						|| T_CLOSE_PARENTHESIS === $tokens[ $after_var ]['code'] )
				) {
					$phpcs_file->addWarning(
						"Variable '%s' used as callback for '%s'. Cannot determine at static analysis time whether this is hookable — manual inspection needed.",
						$value_ptr,
						'VariableCallback',
						array( $tokens[ $value_ptr ]['content'], $function_name )
					);
				}
			}
		}
	}

	/**
	 * Check if the callback value is first-class callable syntax: func(...).
	 *
	 * First-class callables create Closure objects that cannot be unhooked.
	 * Patterns: my_function(...), \Namespace\func(...), MyClass::method(...), $obj->method(...).
	 *
	 * @param File $phpcs_file  The file being scanned.
	 * @param int  $value_ptr   The position of the callback value token.
	 * @param int  $close_paren The position of the enclosing close parenthesis.
	 *
	 * @return bool True if the value is a first-class callable.
	 */
	private function is_first_class_callable( File $phpcs_file, int $value_ptr, int $close_paren ): bool {
		$tokens = $phpcs_file->getTokens();

		// Tokens that form a callable name expression.
		$name_tokens = array(
			T_STRING                   => true,
			T_NS_SEPARATOR             => true,
			T_DOUBLE_COLON             => true,
			T_OBJECT_OPERATOR          => true,
			T_NULLSAFE_OBJECT_OPERATOR => true,
			T_VARIABLE                 => true,
		);

		// Walk through name-like tokens to find what follows.
		$scan_ptr = $value_ptr;

		while ( $scan_ptr < $close_paren && isset( $tokens[ $scan_ptr ] ) ) {
			if ( isset( Tokens::$emptyTokens[ $tokens[ $scan_ptr ]['code'] ] ) ) {
				++$scan_ptr;
				continue;
			}
			if ( isset( $name_tokens[ $tokens[ $scan_ptr ]['code'] ] ) ) {
				++$scan_ptr;
				continue;
			}
			break;
		}

		// If we landed on T_OPEN_PARENTHESIS, check if it contains only T_ELLIPSIS.
		if ( ! isset( $tokens[ $scan_ptr ] )
			|| T_OPEN_PARENTHESIS !== $tokens[ $scan_ptr ]['code']
			|| ! isset( $tokens[ $scan_ptr ]['parenthesis_closer'] )
		) {
			return false;
		}

		$inner_open  = $scan_ptr;
		$inner_close = $tokens[ $scan_ptr ]['parenthesis_closer'];
		$inner_ptr   = $phpcs_file->findNext( Tokens::$emptyTokens, $inner_open + 1, $inner_close, true );

		if ( false === $inner_ptr || T_ELLIPSIS !== $tokens[ $inner_ptr ]['code'] ) {
			return false;
		}

		// Confirm nothing else after the ellipsis.
		$after_ellipsis = $phpcs_file->findNext( Tokens::$emptyTokens, $inner_ptr + 1, $inner_close, true );

		return ( false === $after_ellipsis );
	}

	/**
	 * Check for indirect hook registration via call_user_func / call_user_func_array.
	 *
	 * These wrap hook registration in a way that prevents static analysis
	 * from detecting closure callbacks. Warn for manual inspection.
	 *
	 * @param File  $phpcs_file The file being scanned.
	 * @param int   $stack_ptr  The position of the call_user_func token.
	 * @param array $hook_list  The list of hook function names.
	 *
	 * @return void
	 */
	private function check_indirect_registration( File $phpcs_file, int $stack_ptr, array $hook_list ): void {
		$tokens = $phpcs_file->getTokens();

		// Skip method calls ($obj->call_user_func).
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

		// Find the first argument.
		$first_arg = $phpcs_file->findNext( Tokens::$emptyTokens, $open_paren + 1, $close_paren, true );

		if ( false === $first_arg ) {
			return;
		}

		// Check if the first argument is a string literal matching a hook function.
		if ( T_CONSTANT_ENCAPSED_STRING !== $tokens[ $first_arg ]['code'] ) {
			return;
		}

		// Strip quotes from the string.
		$func_name = trim( $tokens[ $first_arg ]['content'], "\"'" );

		if ( ! in_array( $func_name, $hook_list, true ) ) {
			return;
		}

		$phpcs_file->addWarning(
			"Indirect hook registration via '%s()' for '%s'. Closure detection cannot be performed — manual inspection needed.",
			$stack_ptr,
			'IndirectHookRegistration',
			array( $tokens[ $stack_ptr ]['content'], $func_name )
		);
	}
}
