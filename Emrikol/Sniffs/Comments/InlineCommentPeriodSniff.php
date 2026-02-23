<?php
/**
 * Emrikol Coding Standards.
 *
 * Replaces Squiz.Commenting.InlineComment.InvalidEndChar with a fixable
 * version. This is a direct copy of Squiz's InlineCommentSniff::process()
 * (for T_COMMENT tokens), converted to WP coding style, with enhancements:
 *
 * 1. PHPCS directives (phpcs:ignore, phpcs:disable, etc.) are skipped.
 * 2. The single InvalidEndChar error is split into:
 *    - AppendPeriod (fixable) for comments ending in a Unicode letter (\p{L}).
 *    - InvalidEndChar (non-fixable) for other invalid endings.
 * 3. Extra accepted closing characters/patterns are configurable via properties.
 *
 * The Emrikol ruleset.xml excludes Squiz.Commenting.InlineComment.InvalidEndChar
 * so this sniff is the sole reporter for end-character violations.
 *
 * @package Emrikol\Sniffs\Comments
 */

namespace Emrikol\Sniffs\Comments;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\InlineCommentSniff;

/**
 * Class InlineCommentPeriodSniff
 *
 * Direct clone of Squiz.Commenting.InlineComment end-character detection
 * with fixable period-append support. All code paths copied from Squiz
 * to guarantee identical behavior; enhancements marked with "Emrikol:".
 *
 * Configurable properties allow extra accepted closing characters beyond
 * the default full-stop (.), exclamation mark (!), and question mark (?).
 *
 * @see https://www.php.net/manual/en/regexp.reference.unicode.php PCRE Unicode properties reference.
 */
class InlineCommentPeriodSniff extends InlineCommentSniff {

	/**
	 * Extra accepted closing characters (comma-separated literals).
	 *
	 * Every character in the string is treated as a valid comment-ending
	 * character in addition to the default . ! ? — no separator needed.
	 *
	 * Examples:
	 *
	 *     <!-- Allow colons, semicolons, and brackets -->
	 *     <property name="extra_accepted_closers" value=":;)]}>" />
	 *
	 *     <!-- Include comma itself as an accepted closer -->
	 *     <property name="extra_accepted_closers" value=":;)]}>," />
	 *
	 * @var string
	 */
	public $extra_accepted_closers = '';

	/**
	 * Extra accepted closing pattern (PCRE character class content).
	 *
	 * The value is placed inside a PCRE character class: /[<value>]$/u
	 * Use Unicode property escapes for broad categories.
	 *
	 * Common Unicode property classes:
	 *   \p{So}  — Symbol, Other (most emoji, arrows, dingbats).
	 *   \p{Sm}  — Symbol, Math (+, −, ×, ÷, =, ∴, ∞, etc.).
	 *   \p{Sc}  — Symbol, Currency ($, €, £, ¥, etc.).
	 *   \p{Sk}  — Symbol, Modifier (^, `, ´, etc.).
	 *   \p{N}   — Any number (digits, fractions, Roman numerals).
	 *   \p{P}   — Any punctuation.
	 *
	 * Examples:
	 *
	 *     <!-- Allow all emoji and symbols -->
	 *     <property name="extra_accepted_pattern" value="\p{So}" />
	 *
	 *     <!-- Allow emoji + math symbols -->
	 *     <property name="extra_accepted_pattern" value="\p{So}\p{Sm}" />
	 *
	 *     <!-- Allow any punctuation (very permissive) -->
	 *     <property name="extra_accepted_pattern" value="\p{P}" />
	 *
	 * @see https://www.php.net/manual/en/regexp.reference.unicode.php
	 *
	 * @var string
	 */
	public $extra_accepted_pattern = '';

	/**
	 * Extra accepted closing Unicode hex ranges (comma-separated).
	 *
	 * Each range is 0xHHHH-0xHHHH or a single 0xHHHH codepoint.
	 * Converted internally to PCRE \x{HHHH}-\x{HHHH} ranges.
	 *
	 * Useful when you need to target specific Unicode blocks rather
	 * than broad property classes.
	 *
	 * Common emoji/symbol blocks:
	 *   0x2600-0x26FF    — Miscellaneous Symbols (☀, ♠, ⚡, etc.).
	 *   0x2700-0x27BF    — Dingbats (✂, ✈, ✉, etc.).
	 *   0x1F300-0x1F5FF  — Misc Symbols & Pictographs (🌍, 🎉, etc.).
	 *   0x1F600-0x1F64F  — Emoticons (😀, 😂, 🙏, etc.).
	 *   0x1F680-0x1F6FF  — Transport & Map Symbols (🚀, 🚗, etc.).
	 *   0x1F900-0x1F9FF  — Supplemental Symbols (🤔, 🧠, etc.).
	 *
	 * Example:
	 *
	 *     <property name="extra_accepted_hex_ranges" value="0x1F600-0x1F64F,0x2600-0x26FF" />
	 *
	 * @var string
	 */
	public $extra_accepted_hex_ranges = '';

	/**
	 * Cached combined PCRE character class content.
	 *
	 * @var string|null
	 */
	private $extra_class_cache = null;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * Only T_COMMENT — the parent sniff handles T_DOC_COMMENT_OPEN_TAG
	 * separately and we don't need to override that.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_COMMENT );
	}

	/**
	 * Build the combined PCRE character class content from all extra properties.
	 *
	 * Combines literal characters, PCRE patterns, and hex ranges into a single
	 * string suitable for use inside /[...]$/u.
	 *
	 * @return string Character class content, or empty string if nothing configured.
	 */
	private function get_extra_class_content(): string {
		if ( null !== $this->extra_class_cache ) {
			return $this->extra_class_cache;
		}

		$parts = '';

		// Literal characters — each character in the string is a closer.
		if ( '' !== $this->extra_accepted_closers ) {
			$parts .= preg_quote( $this->extra_accepted_closers, '/' );
		}

		// PCRE character class content (e.g., \p{So}\p{Sm}).
		if ( '' !== $this->extra_accepted_pattern ) {
			$parts .= $this->extra_accepted_pattern;
		}

		// Hex codepoint ranges (e.g., 0x1F600-0x1F64F).
		if ( '' !== $this->extra_accepted_hex_ranges ) {
			$ranges = array_map( 'trim', explode( ',', $this->extra_accepted_hex_ranges ) );
			foreach ( $ranges as $range ) {
				if ( false !== strpos( $range, '-' ) ) {
					$endpoints = explode( '-', $range, 2 );
					$start     = trim( $endpoints[0] );
					$end       = trim( $endpoints[1] );

					// Strip 0x / 0X prefix.
					if ( 0 === stripos( $start, '0x' ) ) {
						$start = substr( $start, 2 );
					}
					if ( 0 === stripos( $end, '0x' ) ) {
						$end = substr( $end, 2 );
					}

					$parts .= "\\x{{$start}}-\\x{{$end}}";
				} else {
					$hex = trim( $range );
					if ( 0 === stripos( $hex, '0x' ) ) {
						$hex = substr( $hex, 2 );
					}

					$parts .= "\\x{{$hex}}";
				}
			}
		}

		$this->extra_class_cache = $parts;
		return $parts;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * This is a direct copy of Squiz\Sniffs\Commenting\InlineCommentSniff::process()
	 * for T_COMMENT tokens, with WP coding style and enhancements marked
	 * "Emrikol:" in comments. Error codes handled by the parent Squiz sniff
	 * (WrongStyle, TabBefore, NoSpaceBefore, SpacingBefore, Empty, NotCapital,
	 * SpacingAfter) are noted but not re-reported to avoid duplication.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return int|void Next token to process, or void.
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Squiz: Perl-style (#) comment handling (WrongStyle) — handled by parent sniff.
		// Falls through to the // check below which filters non-// comments.

		// We don't want end of block comments. Check if the last token before the
		// comment is a closing curly brace.
		$previous_content = $phpcs_file->findPrevious( T_WHITESPACE, ( $stack_ptr - 1 ), null, true );
		if ( $tokens[ $previous_content ]['line'] === $tokens[ $stack_ptr ]['line'] ) {
			if ( $tokens[ $previous_content ]['code'] === T_CLOSE_CURLY_BRACKET ) {
				return;
			}

			// Special case for JS files.
			if ( $tokens[ $previous_content ]['code'] === T_COMMA
				|| $tokens[ $previous_content ]['code'] === T_SEMICOLON
			) {
				$last_content = $phpcs_file->findPrevious( T_WHITESPACE, ( $previous_content - 1 ), null, true );
				if ( $tokens[ $last_content ]['code'] === T_CLOSE_CURLY_BRACKET ) {
					return;
				}
			}
		}

		// Only want inline comments.
		if ( substr( $tokens[ $stack_ptr ]['content'], 0, 2 ) !== '//' ) {
			return;
		}

		$comment_tokens = array( $stack_ptr );

		$next_comment = $stack_ptr;
		$last_comment = $stack_ptr;
		while ( ( $next_comment = $phpcs_file->findNext( T_COMMENT, ( $next_comment + 1 ), null, false ) ) !== false ) {
			if ( $tokens[ $next_comment ]['line'] !== ( $tokens[ $last_comment ]['line'] + 1 ) ) {
				break;
			}

			// Only want inline comments.
			if ( substr( $tokens[ $next_comment ]['content'], 0, 2 ) !== '//' ) {
				break;
			}

			// There is a comment on the very next line. If there is
			// no code between the comments, they are part of the same
			// comment block.
			$prev_non_whitespace = $phpcs_file->findPrevious( T_WHITESPACE, ( $next_comment - 1 ), $last_comment, true );
			if ( $prev_non_whitespace !== $last_comment ) {
				break;
			}

			$comment_tokens[] = $next_comment;
			$last_comment     = $next_comment;
		}

		$comment_text = '';
		foreach ( $comment_tokens as $last_comment_token ) {
			$comment = rtrim( $tokens[ $last_comment_token ]['content'] );

			if ( trim( substr( $comment, 2 ) ) === '' ) {
				continue;
			}

			// Squiz: Spacing checks (TabBefore, NoSpaceBefore, SpacingBefore)
			// happen here in the original. Handled by parent sniff; omitted to
			// avoid double-reporting. Does not affect $comment_text value.

			$comment_text .= trim( substr( $tokens[ $last_comment_token ]['content'], 2 ) );
		}

		if ( $comment_text === '' ) {
			// Squiz: Empty comment error handled by parent sniff.
			return ( $last_comment_token + 1 );
		}

		// Squiz: NotCapital check happens here in the original.
		// Handled by parent sniff; does not affect end-character flow.

		// Emrikol enhancement: Skip PHPCS directives (phpcs:ignore, phpcs:disable, etc.).
		// These start with a letter so they'd pass the prose check below, but flagging
		// them for punctuation is not useful.
		if ( preg_match( '/^phpcs:/i', $comment_text ) === 1 ) {
			return ( $last_comment_token + 1 );
		}

		// Only check the end of comment character if the start of the comment
		// is a letter, indicating that the comment is just standard text.
		if ( preg_match( '/^\p{L}/u', $comment_text ) === 1 ) {
			$comment_closer   = $comment_text[ ( strlen( $comment_text ) - 1 ) ];
			$accepted_closers = array(
				'full-stops'        => '.',
				'exclamation marks' => '!',
				'or question marks' => '?',
			);

			if ( in_array( $comment_closer, $accepted_closers, true ) === false ) {
				// Emrikol enhancement: Check extra configured closers before reporting.
				$extra_class    = $this->get_extra_class_content();
				$extra_accepted = '' !== $extra_class
					&& preg_match( '/[' . $extra_class . ']$/u', $comment_text ) === 1;

				if ( ! $extra_accepted ) {
					// Emrikol enhancement: Split into fixable (letter endings) and
					// non-fixable (other). Squiz reports a single non-fixable
					// InvalidEndChar for all invalid endings.
					//
					// Use \p{L} against the full $comment_text rather than the
					// byte-level $comment_closer — multibyte characters like é
					// would be split across bytes, breaking a per-byte regex.
					if ( preg_match( '/\p{L}$/u', $comment_text ) === 1 ) {
						$error = 'Inline comments must end in full-stops, exclamation marks, or question marks';
						$fix   = $phpcs_file->addFixableError( $error, $last_comment_token, 'AppendPeriod' );

						if ( $fix === true ) {
							$original = $tokens[ $last_comment_token ]['content'];
							$rtrimmed = rtrim( $original );
							$trailing = substr( $original, strlen( $rtrimmed ) );
							$phpcs_file->fixer->replaceToken( $last_comment_token, $rtrimmed . '.' . $trailing );
						}
					} else {
						$error = 'Inline comments must end in %s';
						$ender = '';
						foreach ( $accepted_closers as $closer_name => $symbol ) {
							$ender .= ' ' . $closer_name . ',';
						}

						$ender = trim( $ender, ' ,' );
						$data  = array( $ender );
						$phpcs_file->addError( $error, $last_comment_token, 'InvalidEndChar', $data );
					}
				}
			}
		}

		// Squiz: Blank-line-after checks (SpacingAfter, SpacingAfterAtFunctionEnd)
		// happen here in the original. Handled by parent sniff.

		return ( $last_comment_token + 1 );
	}
}
