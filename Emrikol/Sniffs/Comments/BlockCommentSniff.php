<?php
/**
 * Emrikol Coding Standards.
 *
 * Replaces Squiz.Commenting.BlockComment with enhanced comment style
 * enforcement. This is a direct extension of Squiz's BlockCommentSniff
 * with enhancements:
 *
 * 1. Hash (#) comments are converted to // (HashComment, fixable).
 * 2. Consecutive // comments (min_lines configurable, default 2) are
 *    converted to block comments (WrongStyle, fixable).
 *    - Produces /** when immediately before a declaration (function,
 *      class, interface, trait, enum, property, const).
 *    - Produces /* for all other comment blocks.
 * 3. All existing Squiz block comment formatting checks are preserved
 *    via parent::process() delegation.
 *
 * Multi-pass fixer ordering:
 * - Pass 1: # → // (HashComment), consecutive // → block (WrongStyle).
 * - Pass 2+: Squiz formatting checks on resulting block comments.
 *
 * The Emrikol ruleset.xml should exclude:
 * - Squiz.Commenting.BlockComment (replaced by this sniff).
 * - Squiz.Commenting.InlineComment.WrongStyle (# handling moved here).
 *
 * @package Emrikol\Sniffs\Comments
 */

namespace Emrikol\Sniffs\Comments;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\BlockCommentSniff as SquizBlockCommentSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Class BlockCommentSniff
 *
 * Extends Squiz.Commenting.BlockComment with fixable comment style
 * conversions. Handles # → //, consecutive // → block comments, and
 * delegates all block comment formatting to the parent Squiz sniff.
 *
 * Configurable properties:
 * - min_lines: Minimum consecutive // lines to trigger conversion (default 2).
 */
class BlockCommentSniff extends SquizBlockCommentSniff {

	/**
	 * Minimum number of consecutive // comment lines to trigger conversion.
	 *
	 * Set via ruleset.xml:
	 *
	 *     <rule ref="Emrikol.Comments.BlockComment">
	 *         <properties>
	 *             <property name="min_lines" value="3" />
	 *         </properties>
	 *     </rule>
	 *
	 * @var int
	 */
	public $min_lines = 2;

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * Routes tokens to the appropriate handler:
	 * - # comments → process_hash_comment() for # → // conversion.
	 * - // comments → process_inline_block() for consecutive // detection.
	 * - Everything else → parent Squiz sniff for block comment validation.
	 *
	 * @param File $phpcs_file The current file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return int|void Next token to process, or void.
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		// Emrikol: Convert # comments to //.
		if ( substr( $tokens[ $stack_ptr ]['content'], 0, 1 ) === '#' ) {
			return $this->process_hash_comment( $phpcs_file, $stack_ptr );
		}

		// Emrikol: Consecutive // comments → block comment.
		if ( substr( $tokens[ $stack_ptr ]['content'], 0, 2 ) === '//' ) {
			return $this->process_inline_block( $phpcs_file, $stack_ptr );
		}

		// For /* */ and /** */ tokens, delegate to Squiz parent.
		return parent::process( $phpcs_file, $stack_ptr );
	}

	/**
	 * Convert a # comment to // format.
	 *
	 * Handles all formats:
	 * - "# text\n"  → "// text\n"
	 * - "#text\n"   → "// text\n" (adds space)
	 * - "#\n"       → "//\n"
	 *
	 * @param File $phpcs_file The current file being scanned.
	 * @param int  $stack_ptr  The position of the # comment token.
	 *
	 * @return int Next token to continue processing from.
	 */
	private function process_hash_comment( File $phpcs_file, int $stack_ptr ): int {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		$error = 'Perl-style comments are not allowed; use "// Comment" instead';
		$fix   = $phpcs_file->addFixableError( $error, $stack_ptr, 'HashComment' );

		if ( true === $fix ) {
			$text = substr( $content, 1 ); // Remove the '#'.

			// Add space after // if text doesn't start with one.
			if ( strlen( $text ) > 0
				&& ' ' !== $text[0]
				&& "\n" !== $text[0]
				&& "\r" !== $text[0]
			) {
				$replacement = '// ' . $text;
			} else {
				$replacement = '//' . $text;
			}

			$phpcs_file->fixer->replaceToken( $stack_ptr, $replacement );
		}

		return ( $stack_ptr + 1 );
	}

	/**
	 * Check for consecutive // comment lines and flag for block comment conversion.
	 *
	 * Only processes standalone comments (not inline after code). Collects
	 * consecutive // lines separated only by whitespace, skips groups that
	 * contain PHPCS directives, and offers fixable conversion when the
	 * group meets or exceeds min_lines.
	 *
	 * When the comment group immediately precedes a declaration (function,
	 * class, etc.), the fixer produces a /** docblock. Otherwise, it
	 * produces a /* block comment.
	 *
	 * @param File $phpcs_file The current file being scanned.
	 * @param int  $stack_ptr  The position of the first // comment token.
	 *
	 * @return int Next token to continue processing from.
	 */
	private function process_inline_block( File $phpcs_file, int $stack_ptr ): int {
		$tokens = $phpcs_file->getTokens();

		// Only handle standalone comments (not inline after code).
		$previous_content = $phpcs_file->findPrevious( T_WHITESPACE, ( $stack_ptr - 1 ), null, true );
		if ( false !== $previous_content
			&& $tokens[ $previous_content ]['line'] === $tokens[ $stack_ptr ]['line']
		) {
			return ( $stack_ptr + 1 );
		}

		// Collect consecutive // comment tokens.
		$comment_tokens = array( $stack_ptr );
		$last_comment   = $stack_ptr;

		while ( true ) {
			$next = $phpcs_file->findNext( T_WHITESPACE, ( $last_comment + 1 ), null, true );

			if ( false === $next ) {
				break;
			}

			// Must be on the very next line.
			if ( $tokens[ $next ]['line'] !== ( $tokens[ $last_comment ]['line'] + 1 ) ) {
				break;
			}

			// Must be a // comment.
			if ( T_COMMENT !== $tokens[ $next ]['code']
				|| substr( $tokens[ $next ]['content'], 0, 2 ) !== '//'
			) {
				break;
			}

			// Must be standalone (no code between this and the previous comment).
			// Note: $end in findPrevious is exclusive ($i < $end), but when
			// $start == $end the token at that position is still checked. Use
			// $last_comment + 1 to correctly exclude the previous comment token.
			$prev_non_ws = $phpcs_file->findPrevious( T_WHITESPACE, ( $next - 1 ), ( $last_comment + 1 ), true );
			if ( false !== $prev_non_ws ) {
				break;
			}

			$comment_tokens[] = $next;
			$last_comment     = $next;
		}

		// Not enough consecutive lines — skip.
		if ( count( $comment_tokens ) < (int) $this->min_lines ) {
			return ( $stack_ptr + 1 );
		}

		// Skip groups that contain PHPCS directives (safety check — PHPCS
		// normally tokenizes these as T_PHPCS_* which would break the group,
		// but belt-and-suspenders in case of edge cases).
		foreach ( $comment_tokens as $token_ptr ) {
			$content = $tokens[ $token_ptr ]['content'];
			if ( preg_match( '/^\/\/\s*phpcs:/i', $content ) === 1 ) {
				return ( $last_comment + 1 );
			}
		}

		// Skip groups where any line contains */ — converting to a block
		// comment would produce invalid PHP since */ closes the comment.
		foreach ( $comment_tokens as $token_ptr ) {
			if ( strpos( $tokens[ $token_ptr ]['content'], '*/' ) !== false ) {
				return ( $last_comment + 1 );
			}
		}

		// Skip groups where all lines are empty (bare //) — the Squiz parent
		// deletes empty block comments, causing a fixer conflict loop.
		$has_text = false;
		foreach ( $comment_tokens as $token_ptr ) {
			if ( '' !== $this->strip_comment_prefix( $tokens[ $token_ptr ]['content'] ) ) {
				$has_text = true;
				break;
			}
		}

		if ( ! $has_text ) {
			return ( $last_comment + 1 );
		}

		$error = 'Consecutive single-line comments should be a block comment';
		$fix   = $phpcs_file->addFixableError( $error, $stack_ptr, 'WrongStyle' );

		if ( true === $fix ) {
			$is_docblock = $this->is_before_declaration( $phpcs_file, $last_comment );
			$this->fix_to_block_comment( $phpcs_file, $comment_tokens, $is_docblock );
		}

		return ( $last_comment + 1 );
	}

	/**
	 * Check if the comment group immediately precedes a declaration.
	 *
	 * Looks past whitespace and PHP 8.0+ attributes to find the next
	 * significant token. Returns true if it is a declaration keyword
	 * (function, class, interface, trait, enum, const, var, visibility
	 * modifier, abstract, final, static, readonly).
	 *
	 * @param File $phpcs_file      The current file being scanned.
	 * @param int  $last_comment_ptr Position of the last comment in the group.
	 *
	 * @return bool True if the group precedes a declaration.
	 */
	private function is_before_declaration( File $phpcs_file, int $last_comment_ptr ): bool {
		$tokens = $phpcs_file->getTokens();

		// Find the next non-empty token after the comment group.
		$next = $phpcs_file->findNext( Tokens::$emptyTokens, ( $last_comment_ptr + 1 ), null, true );
		if ( false === $next ) {
			return false;
		}

		// Skip PHP 8.0+ attributes.
		while ( T_ATTRIBUTE === $tokens[ $next ]['code'] ) {
			if ( ! isset( $tokens[ $next ]['attribute_closer'] ) ) {
				break;
			}

			$next = $phpcs_file->findNext( Tokens::$emptyTokens, ( $tokens[ $next ]['attribute_closer'] + 1 ), null, true );
			if ( false === $next ) {
				return false;
			}
		}

		// Check for declaration keywords.
		$declaration_tokens  = Tokens::$scopeModifiers;
		$declaration_tokens += array(
			T_CLASS     => true,
			T_INTERFACE => true,
			T_TRAIT     => true,
			T_ENUM      => true,
			T_FUNCTION  => true,
			T_FINAL     => true,
			T_STATIC    => true,
			T_ABSTRACT  => true,
			T_CONST     => true,
			T_VAR       => true,
			T_READONLY  => true,
		);

		return isset( $declaration_tokens[ $tokens[ $next ]['code'] ] );
	}

	/**
	 * Convert consecutive // comment tokens to a block comment.
	 *
	 * Replaces the first T_COMMENT with the block opener and first line,
	 * middle T_COMMENTs with starred lines, and the last T_COMMENT with
	 * the final line and closer. Intermediate T_WHITESPACE tokens between
	 * comment lines are blanked out.
	 *
	 * @param File  $phpcs_file     The current file being scanned.
	 * @param int[] $comment_tokens Array of T_COMMENT token positions.
	 * @param bool  $is_docblock    Whether to produce /** (true) or /* (false).
	 *
	 * @return void
	 */
	private function fix_to_block_comment( File $phpcs_file, array $comment_tokens, bool $is_docblock ): void {
		$tokens = $phpcs_file->getTokens();
		$eol    = $phpcs_file->eolChar;

		// Determine indentation from whitespace before the first comment.
		$first_token = $comment_tokens[0];
		$indent      = '';
		if ( $first_token > 0 && T_WHITESPACE === $tokens[ ( $first_token - 1 ) ]['code'] ) {
			$ws_content = $tokens[ ( $first_token - 1 ) ]['content'];
			// Only use the part after the last newline (pure indentation).
			$last_newline = strrpos( $ws_content, "\n" );
			if ( false === $last_newline ) {
				// No newline — the whole token is indentation.
				$indent = $ws_content;
			} else {
				$indent = substr( $ws_content, $last_newline + 1 );
			}
		}

		$opener = $is_docblock ? '/**' : '/*';

		$phpcs_file->fixer->beginChangeset();

		$last_index = count( $comment_tokens ) - 1;

		foreach ( $comment_tokens as $i => $token_ptr ) {
			$text = $this->strip_comment_prefix( $tokens[ $token_ptr ]['content'] );

			// Build the starred line, handling empty comment lines (bare //).
			$star_line = ( '' === $text )
				? $indent . ' *' . $eol
				: $indent . ' * ' . $text . $eol;

			if ( 0 === $i && $i === $last_index ) {
				// Single-element group (only possible if min_lines=1).
				$replacement = $opener . $eol . $star_line . $indent . ' */' . $eol;
			} elseif ( 0 === $i ) {
				// First line: open the block comment.
				$replacement = $opener . $eol . $star_line;
			} elseif ( $i === $last_index ) {
				// Last line: comment text + closer.
				$replacement = $star_line . $indent . ' */' . $eol;
			} else {
				// Middle lines.
				$replacement = $star_line;
			}

			$phpcs_file->fixer->replaceToken( $token_ptr, $replacement );

			// Remove whitespace tokens between consecutive comment lines.
			if ( $i < $last_index ) {
				$next_comment = $comment_tokens[ $i + 1 ];
				for ( $j = ( $token_ptr + 1 ); $j < $next_comment; $j++ ) {
					if ( T_WHITESPACE === $tokens[ $j ]['code'] ) {
						$phpcs_file->fixer->replaceToken( $j, '' );
					}
				}
			}
		}

		$phpcs_file->fixer->endChangeset();
	}

	/**
	 * Strip the // prefix and optional leading space from a comment line.
	 *
	 * Handles all common formats:
	 * - "// text\n"  → "text"
	 * - "//text\n"   → "text"
	 * - "//\n"       → ""
	 * - "// \n"      → ""
	 *
	 * @param string $content The raw T_COMMENT content (e.g., "// text\n").
	 *
	 * @return string The comment text without prefix or trailing newline/whitespace.
	 */
	private function strip_comment_prefix( string $content ): string {
		$text = rtrim( $content, "\n\r" );

		if ( substr( $text, 0, 3 ) === '// ' ) {
			$text = substr( $text, 3 );
		} elseif ( substr( $text, 0, 2 ) === '//' ) {
			$text = substr( $text, 2 );
		}

		// Trim trailing whitespace from the extracted text (bare "// " lines).
		return rtrim( $text );
	}
}
