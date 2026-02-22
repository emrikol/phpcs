<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that all PHP files have a namespace declaration.
 *
 * @package Emrikol\Sniffs\Namespaces
 */

namespace Emrikol\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class NamespaceSniff
 *
 * Checks that PHP files have a namespace declaration.
 */
class NamespaceSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_OPEN_TAG );
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
		// Only check once per file (first opening tag).
		$first_open_tag = $phpcs_file->findNext( T_OPEN_TAG, 0 );
		if ( false === $first_open_tag || $stack_ptr !== $first_open_tag ) {
			return;
		}

		$tokens        = $phpcs_file->getTokens();
		$has_namespace = false;

		foreach ( $tokens as $token ) {
			if ( T_NAMESPACE === $token['code'] ) {
				$has_namespace = true;
				break;
			}
		}

		if ( ! $has_namespace ) {
			$phpcs_file->addError(
				'PHP file must have a namespace declaration.',
				$stack_ptr,
				'MissingNamespace'
			);
		}
	}
}
