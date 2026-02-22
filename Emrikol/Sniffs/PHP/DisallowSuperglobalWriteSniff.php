<?php
/**
 * Emrikol Coding Standards.
 *
 * Forbids writing to PHP superglobals ($_GET, $_POST, $_REQUEST,
 * $_SERVER, $_COOKIE). Mutating superglobals creates hidden
 * side-effects and breaks the principle of immutable input.
 *
 * @package Emrikol\Sniffs\PHP
 */

namespace Emrikol\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class DisallowSuperglobalWriteSniff
 *
 * Detects assignment to superglobal variables. Reading superglobals
 * is allowed; only writes (=, +=, .=, etc.) and unset() are flagged.
 */
class DisallowSuperglobalWriteSniff implements Sniff {

	/**
	 * Superglobals to check for writes.
	 *
	 * Comma-separated list configurable via ruleset.xml.
	 *
	 * @var string
	 */
	public $superglobals = '$_GET,$_POST,$_REQUEST,$_SERVER,$_COOKIE,$_SESSION,$_FILES,$_ENV';

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_VARIABLE, T_UNSET );
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

		if ( T_UNSET === $tokens[ $stack_ptr ]['code'] ) {
			$this->check_unset( $phpcs_file, $stack_ptr );
			return;
		}

		$var_name      = $tokens[ $stack_ptr ]['content'];
		$sg_list       = array_map( 'trim', explode( ',', $this->superglobals ) );

		if ( ! in_array( $var_name, $sg_list, true ) ) {
			return;
		}

		// Walk past any array access: $_GET['key'], $_POST['key']['subkey'], etc.
		$scan_ptr = $stack_ptr + 1;

		while ( isset( $tokens[ $scan_ptr ] ) ) {
			if ( isset( Tokens::$emptyTokens[ $tokens[ $scan_ptr ]['code'] ] ) ) {
				++$scan_ptr;
				continue;
			}

			if ( T_OPEN_SQUARE_BRACKET === $tokens[ $scan_ptr ]['code'] ) {
				if ( ! isset( $tokens[ $scan_ptr ]['bracket_closer'] ) ) {
					break;
				}
				$scan_ptr = $tokens[ $scan_ptr ]['bracket_closer'] + 1;
				continue;
			}

			break;
		}

		if ( ! isset( $tokens[ $scan_ptr ] ) ) {
			return;
		}

		// Check if the next non-whitespace token is an assignment operator.
		if ( ! $this->is_assignment_token( $tokens[ $scan_ptr ]['code'] ) ) {
			return;
		}

		$phpcs_file->addError(
			"Direct write to superglobal '%s' is not allowed. Superglobals should be treated as read-only.",
			$stack_ptr,
			'SuperglobalWrite',
			array( $var_name )
		);
	}

	/**
	 * Check unset() calls for superglobal arguments.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  Position of the T_UNSET token.
	 *
	 * @return void
	 */
	private function check_unset( File $phpcs_file, int $stack_ptr ): void {
		$tokens = $phpcs_file->getTokens();

		$open_paren = $phpcs_file->findNext( Tokens::$emptyTokens, $stack_ptr + 1, null, true );

		if ( false === $open_paren || T_OPEN_PARENTHESIS !== $tokens[ $open_paren ]['code'] ) {
			return;
		}

		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];
		$sg_list     = array_map( 'trim', explode( ',', $this->superglobals ) );

		// Scan for superglobal variables inside unset().
		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			if ( T_VARIABLE !== $tokens[ $i ]['code'] ) {
				continue;
			}

			if ( ! in_array( $tokens[ $i ]['content'], $sg_list, true ) ) {
				continue;
			}

			$phpcs_file->addError(
				"Unsetting superglobal '%s' is not allowed. Superglobals should be treated as read-only.",
				$i,
				'SuperglobalUnset',
				array( $tokens[ $i ]['content'] )
			);
		}
	}

	/**
	 * Check if a token code is an assignment operator.
	 *
	 * @param int $code The token code.
	 *
	 * @return bool
	 */
	private function is_assignment_token( int|string $code ): bool {
		return in_array(
			$code,
			array(
				T_EQUAL,
				T_PLUS_EQUAL,
				T_MINUS_EQUAL,
				T_MUL_EQUAL,
				T_DIV_EQUAL,
				T_CONCAT_EQUAL,
				T_MOD_EQUAL,
				T_AND_EQUAL,
				T_OR_EQUAL,
				T_XOR_EQUAL,
				T_SL_EQUAL,
				T_SR_EQUAL,
				T_POW_EQUAL,
				T_COALESCE_EQUAL,
			),
			true
		);
	}
}
