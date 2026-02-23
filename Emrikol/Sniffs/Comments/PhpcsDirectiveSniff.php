<?php
/**
 * Emrikol Coding Standards.
 *
 * Enforces surgical use of PHPCS suppression directives.
 * Bare (un-scoped) phpcs:ignore/disable and phpcs:ignore-file
 * are flagged. Unmatched phpcs:disable (without a corresponding
 * phpcs:enable) is also flagged. Unmatched phpcs:enable (without
 * a prior phpcs:disable) produces a warning. Missing '--' note
 * separators are detected and auto-fixed.
 *
 * @package Emrikol\Sniffs\Comments
 */

namespace Emrikol\Sniffs\Comments;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class PhpcsDirectiveSniff
 *
 * Checks that PHPCS suppression directives are used surgically:
 * - phpcs:ignore must specify sniff codes
 * - phpcs:disable must specify sniff codes
 * - phpcs:disable must have a matching phpcs:enable
 * - phpcs:ignore-file is forbidden
 * - phpcs:enable without prior phpcs:disable is warned about
 * - Legacy @codingStandardsIgnore* directives are converted to modern equivalents
 * - Notes after sniff codes must use the '--' separator
 */
class PhpcsDirectiveSniff implements Sniff {

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

		// Only process on the first open tag.
		$first_open_tag = $phpcs_file->findNext( T_OPEN_TAG, 0 );
		if ( false === $first_open_tag || $stack_ptr !== $first_open_tag ) {
			return;
		}

		// Track open disables for matching: sniff_code => token_ptr.
		$open_disables = array();

		for ( $i = 0; $i < $phpcs_file->numTokens; $i++ ) {
			$token_code = $tokens[ $i ]['code'];

			// Check for legacy directives in comments.
			if ( T_COMMENT === $token_code ) {
				$this->check_legacy_directive( $phpcs_file, $i, $tokens[ $i ]['content'] );
				continue;
			}

			// Check for legacy directives in doc comment tags (/** @codingStandards... */).
			if ( T_DOC_COMMENT_TAG === $token_code ) {
				$this->check_legacy_doc_tag( $phpcs_file, $i );
				continue;
			}

			if ( T_PHPCS_IGNORE === $token_code ) {
				if ( $this->is_bare_directive( $tokens[ $i ] ) ) {
					$this->add_unsuppressable_error(
						$phpcs_file,
						$i,
						'phpcs:ignore directive must specify sniff code(s). Use phpcs:ignore Sniff.Code.Here instead.',
						'BareIgnore'
					);
				} else {
					$this->check_missing_note_separator( $phpcs_file, $i, $tokens[ $i ] );
				}
			} elseif ( T_PHPCS_DISABLE === $token_code ) {
				if ( $this->is_bare_directive( $tokens[ $i ] ) ) {
					$this->add_unsuppressable_error(
						$phpcs_file,
						$i,
						'phpcs:disable directive must specify sniff code(s). Use phpcs:disable Sniff.Code.Here instead.',
						'BareDisable'
					);
				} else {
					$this->check_missing_note_separator( $phpcs_file, $i, $tokens[ $i ] );

					// Track each sniff code for matching.
					foreach ( $tokens[ $i ]['sniffCodes'] as $code => $value ) {
						$open_disables[ $code ] = $i;
					}
				}
			} elseif ( T_PHPCS_ENABLE === $token_code ) {
				if ( $this->is_bare_directive( $tokens[ $i ] ) ) {
					// When sniffCodes is NOT SET (PHPCS quirk: zero active
					// disables), parse raw content to detect targeted enables
					// masquerading as bare. Warn for each unmatched code.
					$raw_codes = $this->parse_codes_from_content( $tokens[ $i ]['content'] );

					if ( ! empty( $raw_codes ) ) {
						foreach ( $raw_codes as $code ) {
							if ( ! isset( $open_disables[ $code ] ) ) {
								$this->add_unsuppressable_warning(
									$phpcs_file,
									$i,
									"phpcs:enable for '%s' has no matching phpcs:disable. This enable may be stale or misplaced.",
									'UnmatchedEnable',
									array( $code )
								);
							} else {
								unset( $open_disables[ $code ] );
							}
						}
					} else {
						// Genuinely bare enable — clears all open disables.
						$open_disables = array();
					}
				} else {
					$this->check_missing_note_separator( $phpcs_file, $i, $tokens[ $i ] );

					// Remove matched codes, warn for unmatched.
					foreach ( $tokens[ $i ]['sniffCodes'] as $code => $value ) {
						if ( ! isset( $open_disables[ $code ] ) ) {
							$this->add_unsuppressable_warning(
								$phpcs_file,
								$i,
								"phpcs:enable for '%s' has no matching phpcs:disable. This enable may be stale or misplaced.",
								'UnmatchedEnable',
								array( $code )
							);
						} else {
							unset( $open_disables[ $code ] );
						}
					}
				}
			} elseif ( T_PHPCS_IGNORE_FILE === $token_code ) {
				$this->add_unsuppressable_error(
					$phpcs_file,
					$i,
					'phpcs:ignore-file is not allowed. Suppress specific sniffs on specific lines instead.',
					'IgnoreFile'
				);
			}
		}

		// Report any unmatched disables.
		foreach ( $open_disables as $code => $token_ptr ) {
			$this->add_unsuppressable_warning(
				$phpcs_file,
				$token_ptr,
				"phpcs:disable for '%s' has no matching phpcs:enable before end of file. Consider adding a phpcs:enable before EOF, or moving the exclusion to .phpcs.xml.dist with an <exclude name=\"%s\"/> rule scoped to this file.",
				'UnmatchedDisable',
				array( $code, $code )
			);
		}
	}

	/**
	 * Check whether a directive token is bare (no specific sniff codes).
	 *
	 * PHPCS represents bare directives in two ways:
	 * - Empty sniffCodes array (e.g. bare phpcs:disable)
	 * - sniffCodes containing only the ".all" key (e.g. bare phpcs:ignore)
	 *
	 * @param array $token The token array.
	 *
	 * @return bool True if the directive is bare (no specific sniff codes).
	 */
	private function is_bare_directive( array $token ): bool {
		if ( ! isset( $token['sniffCodes'] ) || empty( $token['sniffCodes'] ) ) {
			return true;
		}

		// PHPCS expands bare directives to {".all": true}.
		$codes = array_keys( $token['sniffCodes'] );

		return ( 1 === count( $codes ) && '.all' === $codes[0] );
	}

	/**
	 * Parse sniff codes from raw directive token content.
	 *
	 * When PHPCS doesn't populate sniffCodes (e.g. enables with zero active
	 * disables), we fall back to parsing the raw comment text to extract
	 * the sniff code list.
	 *
	 * @param string $content The raw token content (e.g. "// phpcs:enable Foo.Bar, Baz.Qux -- note\n").
	 *
	 * @return array List of sniff code strings. Empty if no codes found (bare directive).
	 */
	private function parse_codes_from_content( string $content ): array {
		// Strip comment markers: //, #, /* ... */.
		$text = trim( $content );
		$text = preg_replace( '#^(?://|\#|/\*)\s*#', '', $text );
		$text = preg_replace( '#\s*\*/$#', '', $text );

		// Remove the directive keyword (phpcs:enable, phpcs:disable, phpcs:ignore).
		$text = preg_replace( '/^phpcs:\w+\s*/', '', $text );
		$text = trim( $text );

		if ( '' === $text ) {
			return array();
		}

		// Split off the note after '--'.
		$parts     = explode( '--', $text, 2 );
		$code_part = trim( $parts[0] );

		if ( '' === $code_part ) {
			return array();
		}

		// Split on commas and trim each code.
		$codes  = explode( ',', $code_part );
		$result = array();

		foreach ( $codes as $code ) {
			$code = trim( $code );
			if ( '' !== $code ) {
				$result[] = $code;
			}
		}

		return $result;
	}

	/**
	 * Check for missing '--' note separator in directive sniff codes.
	 *
	 * When a phpcs directive includes a note without the '--' separator,
	 * PHPCS glues the note text to the last sniff code (e.g.
	 * "Foo.Bar reason text" instead of "Foo.Bar"). This silently breaks
	 * the suppression because the corrupted code won't prefix-match.
	 *
	 * @param File  $phpcs_file The file being scanned.
	 * @param int   $stack_ptr  The position of the directive token.
	 * @param array $token      The token array.
	 *
	 * @return void
	 */
	private function check_missing_note_separator( File $phpcs_file, int $stack_ptr, array $token ): void {
		if ( ! isset( $token['sniffCodes'] ) || empty( $token['sniffCodes'] ) ) {
			return;
		}

		$corrupted_keys = array();

		foreach ( $token['sniffCodes'] as $code => $value ) {
			// Skip the ".all" meta-key.
			if ( '.all' === $code ) {
				continue;
			}

			// Check for '--' in the code first. This means the user intended
			// the separator but PHPCS didn't parse it (requires space before '--').
			// Must check before whitespace since 'Code--note text' has both.
			if ( false !== strpos( $code, '--' ) ) {
				$corrupted_keys[] = array(
					'key'  => $code,
					'type' => 'malformed',
				);
				continue;
			}

			// If a sniff code contains whitespace (space or tab), note text
			// or another code is glued to it — the suppression will fail.
			if ( preg_match( '/\s/', $code ) ) {
				$corrupted_keys[] = array(
					'key'  => $code,
					'type' => 'missing',
				);
			}
		}

		if ( empty( $corrupted_keys ) ) {
			return;
		}

		// Auto-fix only when exactly one code is corrupted (the common case).
		// Multiple corrupted codes means commas split note text ambiguously.
		if ( 1 === count( $corrupted_keys ) ) {
			$corrupted = $corrupted_keys[0];

			if ( 'malformed' === $corrupted['type'] ) {
				$this->report_malformed_separator( $phpcs_file, $stack_ptr, $token, $corrupted['key'] );
			} else {
				$this->report_missing_separator( $phpcs_file, $stack_ptr, $token, $corrupted['key'] );
			}
		} else {
			// Multiple corrupted codes — ambiguous, report but don't auto-fix.
			$first      = $corrupted_keys[0];
			$shown_code = $this->extract_code_from_corrupted( $first );

			$this->add_unsuppressable_error(
				$phpcs_file,
				$stack_ptr,
				"PHPCS directive note for '%s' is missing the '--' separator. Add '--' between sniff codes and note text.",
				'MissingNoteSeparator',
				array( $shown_code )
			);
		}
	}

	/**
	 * Report a malformed '--' separator (present but not recognized by PHPCS).
	 *
	 * PHPCS requires a space before '--' to parse it as a separator.
	 * Without the space, '--' becomes part of the sniff code key.
	 *
	 * @param File   $phpcs_file    The file being scanned.
	 * @param int    $stack_ptr     The position of the directive token.
	 * @param array  $token         The token array.
	 * @param string $corrupted_key The sniffCode key containing '--'.
	 *
	 * @return void
	 */
	private function report_malformed_separator( File $phpcs_file, int $stack_ptr, array $token, string $corrupted_key ): void {
		// Normalize '--' to ' -- ' with proper surrounding whitespace.
		$fixed_key   = preg_replace( '/\s*--\s*/', ' -- ', $corrupted_key, 1 );
		$new_content = str_replace( $corrupted_key, $fixed_key, $token['content'] );

		// Extract the code part (everything before '--').
		$parts      = preg_split( '/\s*--/', $corrupted_key, 2 );
		$shown_code = $parts[0];

		$this->add_unsuppressable_fixable_error(
			$phpcs_file,
			$stack_ptr,
			"PHPCS directive note separator for '%s' is malformed. The '--' requires a space before it to be recognized.",
			'MalformedNoteSeparator',
			array( $shown_code ),
			$new_content
		);
	}

	/**
	 * Report a missing '--' separator (note text glued to sniff code).
	 *
	 * @param File   $phpcs_file    The file being scanned.
	 * @param int    $stack_ptr     The position of the directive token.
	 * @param array  $token         The token array.
	 * @param string $corrupted_key The sniffCode key containing whitespace.
	 *
	 * @return void
	 */
	private function report_missing_separator( File $phpcs_file, int $stack_ptr, array $token, string $corrupted_key ): void {
		$parts      = preg_split( '/\s/', $corrupted_key, 2 );
		$clean_code = $parts[0];
		$note_text  = $parts[1];

		$new_content = str_replace(
			$corrupted_key,
			$clean_code . ' -- ' . $note_text,
			$token['content']
		);

		$this->add_unsuppressable_fixable_error(
			$phpcs_file,
			$stack_ptr,
			"PHPCS directive note for '%s' is missing the '--' separator. Use '-- %s' to separate the note from sniff codes.",
			'MissingNoteSeparator',
			array( $clean_code, $note_text ),
			$new_content
		);
	}

	/**
	 * Extract the sniff code portion from a corrupted key.
	 *
	 * @param array $corrupted Array with 'key' and 'type' entries.
	 *
	 * @return string The sniff code before the note/separator.
	 */
	private function extract_code_from_corrupted( array $corrupted ): string {
		if ( 'malformed' === $corrupted['type'] ) {
			$parts = preg_split( '/\s*--/', $corrupted['key'], 2 );
			return $parts[0];
		}

		$parts = preg_split( '/\s/', $corrupted['key'], 2 );
		return $parts[0];
	}

	/**
	 * Check for legacy @codingStandardsIgnore* directives and offer auto-fix.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the comment token.
	 * @param string $content    The comment content.
	 *
	 * @return void
	 */
	private function check_legacy_directive( File $phpcs_file, int $stack_ptr, string $content ): void {
		$legacy_map = array(
			'@codingStandardsIgnoreLine'  => array(
				'replacement' => 'phpcs:ignore',
				'code'        => 'DeprecatedIgnoreLine',
			),
			'@codingStandardsIgnoreStart' => array(
				'replacement' => 'phpcs:disable',
				'code'        => 'DeprecatedIgnoreStart',
			),
			'@codingStandardsIgnoreEnd'   => array(
				'replacement' => 'phpcs:enable',
				'code'        => 'DeprecatedIgnoreEnd',
			),
		);

		foreach ( $legacy_map as $legacy => $info ) {
			if ( false === strpos( $content, $legacy ) ) {
				continue;
			}

			$new_content = str_replace( $legacy, $info['replacement'], $content );

			$this->add_unsuppressable_fixable_error(
				$phpcs_file,
				$stack_ptr,
				"Deprecated '%s' directive. Use '%s' instead.",
				$info['code'],
				array( $legacy, $info['replacement'] ),
				$new_content
			);

			return;
		}
	}

	/**
	 * Check for legacy @codingStandardsIgnore* directives in doc comment tags.
	 *
	 * Doc comments (/** ... *​/) tokenize @ directives as T_DOC_COMMENT_TAG
	 * rather than T_COMMENT, so they need separate handling.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the doc comment tag token.
	 *
	 * @return void
	 */
	private function check_legacy_doc_tag( File $phpcs_file, int $stack_ptr ): void {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		$legacy_map = array(
			'@codingStandardsIgnoreLine'  => array(
				'replacement' => 'phpcs:ignore',
				'code'        => 'DeprecatedIgnoreLine',
			),
			'@codingStandardsIgnoreStart' => array(
				'replacement' => 'phpcs:disable',
				'code'        => 'DeprecatedIgnoreStart',
			),
			'@codingStandardsIgnoreEnd'   => array(
				'replacement' => 'phpcs:enable',
				'code'        => 'DeprecatedIgnoreEnd',
			),
		);

		foreach ( $legacy_map as $legacy => $info ) {
			if ( false === strpos( $content, $legacy ) ) {
				continue;
			}

			// Find the doc comment boundaries by searching backwards for the open tag.
			$opener = $phpcs_file->findPrevious( T_DOC_COMMENT_OPEN_TAG, $stack_ptr - 1 );

			if ( false === $opener || ! isset( $tokens[ $opener ]['comment_closer'] ) ) {
				return;
			}

			$closer = $tokens[ $opener ]['comment_closer'];

			// Temporarily clear suppression for this line.
			$line       = $tokens[ $stack_ptr ]['line'];
			$had_ignore = isset( $phpcs_file->tokenizer->ignoredLines[ $line ] );

			if ( $had_ignore ) {
				$saved = $phpcs_file->tokenizer->ignoredLines[ $line ];
				unset( $phpcs_file->tokenizer->ignoredLines[ $line ] );
			}

			$fix = $phpcs_file->addFixableError(
				"Deprecated '%s' directive. Use '%s' instead.",
				$stack_ptr,
				$info['code'],
				array( $legacy, $info['replacement'] )
			);

			if ( $fix ) {
				// Replace the entire doc comment with a block comment.
				$phpcs_file->fixer->beginChangeset();
				$phpcs_file->fixer->replaceToken( $opener, '/* ' . $info['replacement'] . ' */' );

				for ( $j = $opener + 1; $j <= $closer; $j++ ) {
					$phpcs_file->fixer->replaceToken( $j, '' );
				}

				$phpcs_file->fixer->endChangeset();
			}

			// Restore suppression state.
			if ( $had_ignore ) {
				$phpcs_file->tokenizer->ignoredLines[ $line ] = $saved;
			}

			return;
		}
	}

	/**
	 * Add an error that cannot be suppressed by phpcs:ignore on the same line.
	 *
	 * Temporarily clears the ignored-lines entry for the target line,
	 * reports the error, then restores the original suppression state.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the token.
	 * @param string $message    The error message.
	 * @param string $code       The error code.
	 * @param array  $data       Optional data for the error message.
	 *
	 * @return void
	 */
	private function add_unsuppressable_error( File $phpcs_file, int $stack_ptr, string $message, string $code, array $data = array() ): void {
		$tokens = $phpcs_file->getTokens();
		$line   = $tokens[ $stack_ptr ]['line'];

		// Save and temporarily clear any suppression for this line.
		$ignored_lines = $phpcs_file->tokenizer->ignoredLines;
		$had_ignore    = isset( $phpcs_file->tokenizer->ignoredLines[ $line ] );

		if ( $had_ignore ) {
			unset( $phpcs_file->tokenizer->ignoredLines[ $line ] );
		}

		$phpcs_file->addError( $message, $stack_ptr, $code, $data );

		// Restore original suppression state.
		if ( $had_ignore ) {
			$phpcs_file->tokenizer->ignoredLines = $ignored_lines;
		}
	}

	/**
	 * Add a warning that cannot be suppressed by phpcs:ignore on the same line.
	 *
	 * PHPCS marks all directive lines (disable, enable, ignore) as suppressed
	 * in ignoredLines with {".all": true}, which would silently swallow our
	 * warnings. This method temporarily clears the suppression to ensure the
	 * warning is recorded.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the token.
	 * @param string $message    The warning message.
	 * @param string $code       The warning code.
	 * @param array  $data       Optional data for the warning message.
	 *
	 * @return void
	 */
	private function add_unsuppressable_warning( File $phpcs_file, int $stack_ptr, string $message, string $code, array $data = array() ): void {
		$tokens = $phpcs_file->getTokens();
		$line   = $tokens[ $stack_ptr ]['line'];

		// Save and temporarily clear any suppression for this line.
		$ignored_lines = $phpcs_file->tokenizer->ignoredLines;
		$had_ignore    = isset( $phpcs_file->tokenizer->ignoredLines[ $line ] );

		if ( $had_ignore ) {
			unset( $phpcs_file->tokenizer->ignoredLines[ $line ] );
		}

		$phpcs_file->addWarning( $message, $stack_ptr, $code, $data );

		// Restore original suppression state.
		if ( $had_ignore ) {
			$phpcs_file->tokenizer->ignoredLines = $ignored_lines;
		}
	}

	/**
	 * Add a fixable error that cannot be suppressed by phpcs:ignore on the same line.
	 *
	 * Temporarily clears the ignored-lines entry for the target line,
	 * reports the fixable error, applies the fix if accepted, then restores
	 * the original suppression state.
	 *
	 * @param File   $phpcs_file  The file being scanned.
	 * @param int    $stack_ptr   The position of the token.
	 * @param string $message     The error message.
	 * @param string $code        The error code.
	 * @param array  $data        Optional data for the error message.
	 * @param string $new_content The replacement token content for the fix.
	 *
	 * @return void
	 */
	private function add_unsuppressable_fixable_error( File $phpcs_file, int $stack_ptr, string $message, string $code, array $data, string $new_content ): void {
		$tokens = $phpcs_file->getTokens();
		$line   = $tokens[ $stack_ptr ]['line'];

		// Save and temporarily clear any suppression for this line.
		$ignored_lines = $phpcs_file->tokenizer->ignoredLines;
		$had_ignore    = isset( $phpcs_file->tokenizer->ignoredLines[ $line ] );

		if ( $had_ignore ) {
			unset( $phpcs_file->tokenizer->ignoredLines[ $line ] );
		}

		$fix = $phpcs_file->addFixableError( $message, $stack_ptr, $code, $data );

		if ( $fix ) {
			$phpcs_file->fixer->replaceToken( $stack_ptr, $new_content );
		}

		// Restore original suppression state.
		if ( $had_ignore ) {
			$phpcs_file->tokenizer->ignoredLines = $ignored_lines;
		}
	}
}
