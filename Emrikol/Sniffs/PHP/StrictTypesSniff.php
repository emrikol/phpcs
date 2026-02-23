<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that all PHP files contain a declare(strict_types=1) statement.
 * Supports auto-fixing via phpcbf.
 *
 * @package Emrikol\Sniffs\PHP
 */

namespace Emrikol\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class StrictTypesSniff
 *
 * Checks that PHP files have declare(strict_types=1) at the top.
 * When missing, phpcbf can auto-fix by inserting the declaration after
 * the opening PHP tag and any file-level docblock.
 */
class StrictTypesSniff implements Sniff {

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
		$tokens = $phpcs_file->getTokens();

		// Only check the first opening tag in the file.
		$first_open_tag = $phpcs_file->findNext( T_OPEN_TAG, 0 );
		if ( false === $first_open_tag || $stack_ptr !== $first_open_tag ) {
			return;
		}

		// Skip whitespace, comments, all docblock sub-tokens, and PHPCS
		// directive tokens (which the tokenizer injects into doc comments
		// when phpcs:disable/enable/ignore directives appear there).
		$skip_tokens = array(
			T_WHITESPACE,
			T_COMMENT,
			T_DOC_COMMENT,
			T_DOC_COMMENT_OPEN_TAG,
			T_DOC_COMMENT_CLOSE_TAG,
			T_DOC_COMMENT_STRING,
			T_DOC_COMMENT_STAR,
			T_DOC_COMMENT_TAG,
			T_DOC_COMMENT_WHITESPACE,
			T_PHPCS_DISABLE,
			T_PHPCS_ENABLE,
			T_PHPCS_IGNORE,
			T_PHPCS_IGNORE_FILE,
			T_PHPCS_SET,
		);

		$next_meaningful = $phpcs_file->findNext(
			$skip_tokens,
			( $stack_ptr + 1 ),
			null,
			true
		);

		if ( false !== $next_meaningful && T_DECLARE === $tokens[ $next_meaningful ]['code'] ) {
			// Found a declare statement — verify it's strict_types=1.
			$open_paren = $phpcs_file->findNext( T_OPEN_PARENTHESIS, $next_meaningful );
			if ( false === $open_paren ) {
				$this->add_error( $phpcs_file, $stack_ptr );
				return;
			}

			$close_paren = $phpcs_file->findNext( T_CLOSE_PARENTHESIS, $open_paren );
			if ( false === $close_paren ) {
				$this->add_error( $phpcs_file, $stack_ptr );
				return;
			}

			$declare_content = '';
			for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
				if ( T_WHITESPACE !== $tokens[ $i ]['code'] ) {
					$declare_content .= $tokens[ $i ]['content'];
				}
			}

			if ( false === strpos( $declare_content, 'strict_types=1' ) ) {
				$this->add_error( $phpcs_file, $stack_ptr );
			}

			return;
		}

		// No declare statement found.
		// Only auto-fix when the file starts with a PHP open tag.
		// Template partials (HTML before the first open tag) get a
		// non-fixable error because adding strict_types automatically
		// could change type coercion behavior in unexpected ways.
		if ( T_INLINE_HTML === $tokens[0]['code'] ) {
			$this->add_error( $phpcs_file, $stack_ptr );
			return;
		}

		$this->add_fixable_error( $phpcs_file, $stack_ptr );
	}

	/**
	 * Add a non-fixable error (declare exists but with wrong value).
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token.
	 *
	 * @return void
	 */
	private function add_error( File $phpcs_file, int $stack_ptr ): void {
		$phpcs_file->addError(
			'PHP file must contain declare(strict_types=1); after the opening PHP tag',
			$stack_ptr,
			'MissingStrictTypes'
		);
	}

	/**
	 * Add a fixable error and insert declare(strict_types=1) when fixing.
	 *
	 * Only called for files that start with a PHP open tag. Inserts after
	 * any file-level docblock or directly after the opening tag.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the opening PHP tag.
	 *
	 * @return void
	 */
	private function add_fixable_error( File $phpcs_file, int $stack_ptr ): void {
		$fix = $phpcs_file->addFixableError(
			'PHP file must contain declare(strict_types=1); after the opening PHP tag',
			$stack_ptr,
			'MissingStrictTypes'
		);

		if ( ! $fix ) {
			return;
		}

		$tokens = $phpcs_file->getTokens();

		// Insert after any file-level docblock, or after the open tag.
		$insert_after = $stack_ptr;

		// Check if there's a docblock immediately after the opening tag (skipping whitespace).
		$next_non_ws = $phpcs_file->findNext( T_WHITESPACE, $stack_ptr + 1, null, true );
		if ( false !== $next_non_ws && T_DOC_COMMENT_OPEN_TAG === $tokens[ $next_non_ws ]['code'] ) {
			// Insert after the docblock close tag.
			$insert_after = $tokens[ $next_non_ws ]['comment_closer'];
		}

		$phpcs_file->fixer->addContent(
			$insert_after,
			$phpcs_file->eolChar . $phpcs_file->eolChar . 'declare(strict_types=1);'
		);
	}
}
