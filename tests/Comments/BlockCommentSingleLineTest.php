<?php

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Fixer output tests for the single-line block comment conversion.
 *
 * Squiz.Commenting.BlockComment builds the replacement for a single-line
 * block comment as '// ' . $commentText . $phpcsFile->eolChar. The newline
 * that follows a block comment token is a SEPARATE T_WHITESPACE token, so
 * the parent's appended eolChar lands on top of it and the fixed file
 * gains a spurious blank line. Emrikol.Comments.BlockComment inherited
 * that through parent::process().
 *
 * These tests assert the byte-exact fixer output, because a test that only
 * counts error codes passes while the blank line is still there.
 *
 * Covered:
 * - Conversion at file scope, inside a function, and inside an array literal.
 * - A "translators:" block comment note above a __() call (wp i18n make-pot
 *   attaches translator comments by adjacency, so a blank line drops the
 *   note from the POT file).
 * - The last line of a file, with and without a trailing newline.
 * - CRLF line endings, where eolChar is "\r\n".
 * - A block comment with code after it on the same line, which is reported
 *   but must never be rewritten.
 * - Fixer convergence on a file that mixes #, // and block comments.
 */
class BlockCommentSingleLineTest extends BaseSniffTestCase {

	/**
	 * Sniff code for assertions.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Comments.BlockComment';

	/**
	 * Assert that phpcbf turns a fixture into its .fixed counterpart byte for byte.
	 *
	 * Also asserts that the fixed output is a fixed point: running the sniff
	 * over the result must leave it unchanged and report nothing more to fix.
	 *
	 * @param string $fixture The fixture filename (without the .fixed suffix).
	 *
	 * @return void
	 */
	private function assert_fixes_to( string $fixture ): void {
		$expected = file_get_contents( $this->get_fixture_path( $fixture . '.fixed' ) );

		$file  = $this->check_file( $this->get_fixture_path( $fixture ), self::SNIFF_CODE );
		$fixed = $this->get_fixed_content( $file );

		$this->assertSame(
			$expected,
			$fixed,
			"Fixed output for {$fixture} does not match {$fixture}.fixed byte for byte."
		);

		$this->assert_is_fixed_point( $fixed, $fixture );
	}

	/**
	 * Assert that re-running the fixer over already-fixed content changes nothing.
	 *
	 * @param string $content The fixed file content.
	 * @param string $label   Fixture name, for the failure message.
	 *
	 * @return void
	 */
	private function assert_is_fixed_point( string $content, string $label ): void {
		$temp = tempnam( sys_get_temp_dir(), 'emrikol-' ) . '.inc';
		file_put_contents( $temp, $content );

		try {
			$file    = $this->check_file( $temp, self::SNIFF_CODE );
			$refixed = $this->get_fixed_content( $file );

			$this->assertSame(
				0,
				$file->getFixableCount(),
				"Fixed output for {$label} still reports fixable errors; the fixer has not converged."
			);
			$this->assertSame(
				$content,
				$refixed,
				"Fixed output for {$label} is not stable; a second phpcbf pass changes it again."
			);
		} finally {
			unlink( $temp );
		}
	}

	/**
	 * Test that a single-line block comment converts without gaining a blank line.
	 *
	 * Covers file scope, inside a function, inside an array literal, a
	 * translators note above a __() call, and a comment trailing code on the
	 * same line. The comment with code after it on line 25 must survive
	 * untouched.
	 *
	 * @return void
	 */
	public function test_single_line_fixer_output(): void {
		$this->assert_fixes_to( 'block-comment-single-line.inc' );
	}

	/**
	 * Test that every single-line block comment is reported.
	 *
	 * @return void
	 */
	public function test_single_line_error_codes(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-single-line.inc' ),
			self::SNIFF_CODE
		);

		foreach ( array( 4, 9, 15, 22, 25 ) as $line ) {
			$this->assert_error_code_on_line( $file, $line, self::SNIFF_CODE . '.SingleLine' );
		}
	}

	/**
	 * Test that the comment on line 25 is reported but NOT fixable.
	 *
	 * Code follows it on the same line, so rewriting it to // would comment
	 * out the statement.
	 *
	 * @return void
	 */
	public function test_comment_before_code_on_same_line_is_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-single-line.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame(
			4,
			$file->getFixableCount(),
			'Expected 4 fixable errors; the comment with code after it must not be fixable.'
		);
	}

	/**
	 * Test the translators note is left alone, directly above the __() call.
	 *
	 * Gettext attaches a comment to a call only when the comment ends on
	 * the same line or the line directly above (ParsedComment::isRelatedWith),
	 * so anything that moves the note drops it from the POT file.
	 *
	 * @return void
	 */
	public function test_translators_comment_stays_adjacent(): void {
		$file  = $this->check_file(
			$this->get_fixture_path( 'block-comment-single-line.inc' ),
			self::SNIFF_CODE
		);
		$lines = explode( "\n", $this->get_fixed_content( $file ) );

		$this->assertSame( '/* translators: %s: thing. */', $lines[18] );
		$this->assertStringContainsString( 'esc_html__(', $lines[19] );
	}

	/**
	 * Test that "translators:" notes keep their block comment form.
	 *
	 * WordPress core style writes translator notes as single-line block
	 * comments, and WPCS excludes Squiz.Commenting.BlockComment.SingleLine
	 * for exactly this reason. Rewriting them to // would also trip
	 * Squiz.Commenting.InlineComment.NotCapital, which our ruleset leaves
	 * enabled.
	 *
	 * @return void
	 */
	public function test_translators_notes_are_exempt(): void {
		$this->assert_fixes_to( 'block-comment-translators.inc' );
	}

	/**
	 * Test which comments the translators exemption covers.
	 *
	 * Lowercase and capitalized notes are exempt. A comment that merely
	 * sits near a gettext call is not, and neither is /*translators: ...
	 * without the space after the opener, because WPCS's own pattern
	 * requires it.
	 *
	 * @return void
	 */
	public function test_translators_exemption_scope(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-translators.inc' ),
			self::SNIFF_CODE
		);

		foreach ( array( 3, 6, 9 ) as $line ) {
			$this->assert_no_error_on_line( $file, $line );
		}

		foreach ( array( 12, 15 ) as $line ) {
			$this->assert_error_code_on_line( $file, $line, self::SNIFF_CODE . '.SingleLine' );
		}
	}

	/**
	 * Test conversion on the last line of a file that ends with a newline.
	 *
	 * @return void
	 */
	public function test_eof_with_trailing_newline(): void {
		$this->assert_fixes_to( 'block-comment-single-line-eof-newline.inc' );
	}

	/**
	 * Test conversion on the last line of a file with no trailing newline.
	 *
	 * The sniff must not invent a final newline; adding one is
	 * Files.EndFileNewline's job.
	 *
	 * @return void
	 */
	public function test_eof_without_trailing_newline(): void {
		$this->assert_fixes_to( 'block-comment-single-line-eof-no-newline.inc' );
	}

	/**
	 * Test conversion in a file with CRLF line endings.
	 *
	 * @return void
	 */
	public function test_crlf_line_endings(): void {
		$this->assert_fixes_to( 'block-comment-single-line-crlf.inc' );
	}

	/**
	 * Test that the fixer converges on a file mixing #, // and block comments.
	 *
	 * The multi-pass conversion (# to //, consecutive // to a block comment,
	 * single-line block comment to //) must reach a stable file rather than
	 * ping-pong between styles.
	 *
	 * @return void
	 */
	public function test_mixed_comment_styles_converge(): void {
		$file  = $this->check_file(
			$this->get_fixture_path( 'block-comment-mixed-styles.inc' ),
			self::SNIFF_CODE
		);
		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( "// Single line block.\n\$c = 3;", $fixed );
		$this->assert_is_fixed_point( $fixed, 'block-comment-mixed-styles.inc' );
	}
}
