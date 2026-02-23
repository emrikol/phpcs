<?php

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for the InlineCommentPeriod sniff.
 *
 * Replaces Squiz.Commenting.InlineComment.InvalidEndChar with a fixable
 * version. Comments ending in [a-zA-Z] get AppendPeriod (fixable);
 * other invalid endings get InvalidEndChar (non-fixable).
 *
 * Aggregates consecutive // comments into blocks (matching Squiz behavior)
 * and only checks the end character of the last line.
 */
class InlineCommentPeriodSniffTest extends BaseSniffTestCase {

	/**
	 * Sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF = 'Emrikol.Comments.InlineCommentPeriod';

	/**
	 * Fixable error code for comments ending in a letter.
	 *
	 * @var string
	 */
	private const APPEND_PERIOD = 'Emrikol.Comments.InlineCommentPeriod.AppendPeriod';

	/**
	 * Non-fixable error code for other invalid endings.
	 *
	 * @var string
	 */
	private const INVALID_END = 'Emrikol.Comments.InlineCommentPeriod.InvalidEndChar';

	/**
	 * Test fixable errors on single-line comments ending in [a-zA-Z].
	 *
	 * @return void
	 */
	public function test_fixable_errors_on_letter_endings(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Line 4: single comment ending with a letter.
		$this->assert_error_code_on_line( $file, 4, self::APPEND_PERIOD );

		// Line 21: method-level comment inside function.
		$this->assert_error_code_on_line( $file, 21, self::APPEND_PERIOD );

		// Line 22: inline trailing comment after code.
		$this->assert_error_code_on_line( $file, 22, self::APPEND_PERIOD );
	}

	/**
	 * Test non-fixable errors on comments ending in non-letter, non-punctuation.
	 *
	 * @return void
	 */
	public function test_non_fixable_errors_on_non_letter_endings(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Line 18: single comment ending in ')'.
		$this->assert_error_code_on_line( $file, 18, self::INVALID_END );
	}

	/**
	 * Test multi-line comment blocks — only the last line is checked.
	 *
	 * @return void
	 */
	public function test_multiline_block_checks_last_line(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Lines 26-28: multi-line block ending in a letter.
		// Only the last line (28) should have the error.
		$this->assert_no_error_on_line( $file, 26 );
		$this->assert_no_error_on_line( $file, 27 );
		$this->assert_error_code_on_line( $file, 28, self::APPEND_PERIOD );
	}

	/**
	 * Test multi-line block ending with valid punctuation is clean.
	 *
	 * @return void
	 */
	public function test_multiline_block_valid_ending(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Lines 30-31: multi-line block ending with period.
		$this->assert_no_error_on_line( $file, 30 );
		$this->assert_no_error_on_line( $file, 31 );
	}

	/**
	 * Test multi-line block ending in non-letter gets non-fixable error.
	 *
	 * @return void
	 */
	public function test_multiline_block_non_letter_ending(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Lines 33-34: multi-line block ending in ')'.
		$this->assert_no_error_on_line( $file, 33 );
		$this->assert_error_code_on_line( $file, 34, self::INVALID_END );
	}

	/**
	 * Test that comments with valid endings produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_on_valid_endings(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Lines 6, 8, 10: end in . ! ?
		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 8 );
		$this->assert_no_error_on_line( $file, 10 );

		// Line 23: already ends with period.
		$this->assert_no_error_on_line( $file, 23 );
	}

	/**
	 * Test that PHPCS directives and annotations are skipped.
	 *
	 * @return void
	 */
	public function test_no_errors_on_directives(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Line 12: phpcs directive.
		$this->assert_no_error_on_line( $file, 12 );

		// Line 14: @var annotation.
		$this->assert_no_error_on_line( $file, 14 );
	}

	/**
	 * Test that comments starting with non-letters are skipped.
	 *
	 * @return void
	 */
	public function test_no_errors_on_non_prose_comments(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Line 16: starts with digit.
		$this->assert_no_error_on_line( $file, 16 );
	}

	/**
	 * Test that end-of-block comments after closing braces are skipped.
	 *
	 * Matches Squiz behavior: comments on the same line as a closing
	 * curly brace are not checked for end-character punctuation.
	 *
	 * @return void
	 */
	public function test_no_errors_on_closing_brace_comments(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		// Line 39: } // end if — skipped because it follows a closing brace.
		$this->assert_no_error_on_line( $file, 39 );
	}

	/**
	 * Test total error count: 4 fixable + 2 non-fixable = 6.
	 *
	 * @return void
	 */
	public function test_total_error_count(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		$this->assertSame( 6, $file->getErrorCount() );
	}

	/**
	 * Test that the fixer appends periods correctly.
	 *
	 * Only the 4 fixable (AppendPeriod) violations are fixed.
	 * The 2 non-fixable (InvalidEndChar) violations are left alone.
	 *
	 * @return void
	 */
	public function test_fixer_appends_period(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'inline-comment-period.inc' ),
			self::SNIFF
		);

		$fixed    = $this->get_fixed_content( $file );
		$expected = file_get_contents( $this->get_fixture_path( 'inline-comment-period.inc.fixed' ) );

		$this->assertSame( $expected, $fixed );
	}

	// ---------------------------------------------------------------
	// Edge case tests (inline-comment-period-edge-cases.inc fixture).
	// ---------------------------------------------------------------

	/**
	 * Helper to load the edge case fixture.
	 *
	 * @return \PHP_CodeSniffer\Files\File
	 */
	private function get_edge_case_file(): \PHP_CodeSniffer\Files\File {
		return $this->check_file(
			$this->get_fixture_path( 'inline-comment-period-edge-cases.inc' ),
			self::SNIFF
		);
	}

	/**
	 * Test Unicode letter endings are fixable (AppendPeriod).
	 *
	 * Comments ending in non-ASCII letters like é, コ should be fixable
	 * just like ASCII letter endings.
	 *
	 * @return void
	 */
	public function test_unicode_letter_endings_are_fixable(): void {
		$file = $this->get_edge_case_file();

		// Line 4: café — ends in é (Latin small e with acute).
		$this->assert_error_code_on_line( $file, 4, self::APPEND_PERIOD );

		// Line 6: résumé — ends in é.
		$this->assert_error_code_on_line( $file, 6, self::APPEND_PERIOD );

		// Line 8: コメント — ends in ト (Katakana).
		$this->assert_error_code_on_line( $file, 8, self::APPEND_PERIOD );
	}

	/**
	 * Test emoji and symbol endings are non-fixable (InvalidEndChar).
	 *
	 * @return void
	 */
	public function test_emoji_endings_are_not_fixable(): void {
		$file = $this->get_edge_case_file();

		// Line 10: 🚀 emoji.
		$this->assert_error_code_on_line( $file, 10, self::INVALID_END );
	}

	/**
	 * Test digit endings are non-fixable (InvalidEndChar).
	 *
	 * @return void
	 */
	public function test_digit_endings(): void {
		$file = $this->get_edge_case_file();

		// Line 12: "PHP 8" — ends in digit.
		$this->assert_error_code_on_line( $file, 12, self::INVALID_END );

		// Line 14: "Version 2.0" — ends in 0, not the decimal point.
		$this->assert_error_code_on_line( $file, 14, self::INVALID_END );
	}

	/**
	 * Test various punctuation endings that are NOT valid closers.
	 *
	 * @return void
	 */
	public function test_non_closer_punctuation_endings(): void {
		$file = $this->get_edge_case_file();

		// Line 16: colon.
		$this->assert_error_code_on_line( $file, 16, self::INVALID_END );

		// Line 18: semicolon.
		$this->assert_error_code_on_line( $file, 18, self::INVALID_END );

		// Line 20: closing bracket.
		$this->assert_error_code_on_line( $file, 20, self::INVALID_END );
	}

	/**
	 * Test valid closers produce no errors.
	 *
	 * @return void
	 */
	public function test_edge_valid_closers(): void {
		$file = $this->get_edge_case_file();

		// Line 22: period.
		$this->assert_no_error_on_line( $file, 22 );

		// Line 24: exclamation.
		$this->assert_no_error_on_line( $file, 24 );

		// Line 26: question mark.
		$this->assert_no_error_on_line( $file, 26 );
	}

	/**
	 * Test non-prose starts are skipped (no error regardless of ending).
	 *
	 * @return void
	 */
	public function test_non_prose_starts_skipped(): void {
		$file = $this->get_edge_case_file();

		// Line 28: starts with digit.
		$this->assert_no_error_on_line( $file, 28 );

		// Line 30: starts with dash.
		$this->assert_no_error_on_line( $file, 30 );

		// Line 32: starts with asterisk.
		$this->assert_no_error_on_line( $file, 32 );

		// Line 34: starts with open paren.
		$this->assert_no_error_on_line( $file, 34 );

		// Line 36: starts with open bracket.
		$this->assert_no_error_on_line( $file, 36 );

		// Line 38: starts with @.
		$this->assert_no_error_on_line( $file, 38 );

		// Line 40: starts with → (Unicode symbol, not \p{L}).
		$this->assert_no_error_on_line( $file, 40 );
	}

	/**
	 * Test all phpcs directive variations are skipped.
	 *
	 * @return void
	 */
	public function test_phpcs_directive_variations(): void {
		$file = $this->get_edge_case_file();

		// Line 42: phpcs:ignore.
		$this->assert_no_error_on_line( $file, 42 );

		// Line 44: phpcs:disable.
		$this->assert_no_error_on_line( $file, 44 );

		// Line 46: phpcs:enable.
		$this->assert_no_error_on_line( $file, 46 );

		// Line 48: PHPCS:IGNORE (uppercase).
		$this->assert_no_error_on_line( $file, 48 );
	}

	/**
	 * Test empty comments produce no errors.
	 *
	 * @return void
	 */
	public function test_empty_comments_skipped(): void {
		$file = $this->get_edge_case_file();

		// Line 50: bare //.
		$this->assert_no_error_on_line( $file, 50 );

		// Line 52: bare //.
		$this->assert_no_error_on_line( $file, 52 );
	}

	/**
	 * Test closing brace comments are skipped.
	 *
	 * @return void
	 */
	public function test_edge_closing_brace_comment(): void {
		$file = $this->get_edge_case_file();

		// Line 57: } // end if.
		$this->assert_no_error_on_line( $file, 57 );
	}

	/**
	 * Test URL ending in a letter is fixable.
	 *
	 * @return void
	 */
	public function test_url_ending(): void {
		$file = $this->get_edge_case_file();

		// Line 60: See https://example.com/path.
		$this->assert_error_code_on_line( $file, 60, self::APPEND_PERIOD );
	}

	/**
	 * Test single-character comments are handled correctly.
	 *
	 * @return void
	 */
	public function test_single_character_comments(): void {
		$file = $this->get_edge_case_file();

		// Line 62: "a" — single lowercase letter.
		$this->assert_error_code_on_line( $file, 62, self::APPEND_PERIOD );

		// Line 64: "Z" — single uppercase letter.
		$this->assert_error_code_on_line( $file, 64, self::APPEND_PERIOD );
	}

	/**
	 * Test block with empty // line in the middle aggregates correctly.
	 *
	 * @return void
	 */
	public function test_block_with_empty_middle_line(): void {
		$file = $this->get_edge_case_file();

		// Lines 66-68: block with empty // on line 67.
		// Only last line (68) gets the error.
		$this->assert_no_error_on_line( $file, 66 );
		$this->assert_no_error_on_line( $file, 67 );
		$this->assert_error_code_on_line( $file, 68, self::APPEND_PERIOD );
	}

	/**
	 * Test block starting with empty // aggregates correctly.
	 *
	 * @return void
	 */
	public function test_block_starting_with_empty_line(): void {
		$file = $this->get_edge_case_file();

		// Lines 70-71: empty // then text.
		// Only last line (71) gets the error.
		$this->assert_no_error_on_line( $file, 70 );
		$this->assert_error_code_on_line( $file, 71, self::APPEND_PERIOD );
	}

	/**
	 * Test long multi-line block — only last line flagged.
	 *
	 * @return void
	 */
	public function test_long_multi_line_block(): void {
		$file = $this->get_edge_case_file();

		// Lines 73-76: 4-line block ending in letter.
		$this->assert_no_error_on_line( $file, 73 );
		$this->assert_no_error_on_line( $file, 74 );
		$this->assert_no_error_on_line( $file, 75 );
		$this->assert_error_code_on_line( $file, 76, self::APPEND_PERIOD );
	}

	/**
	 * Test multi-line block with valid ending has no errors.
	 *
	 * @return void
	 */
	public function test_edge_multiline_valid_ending(): void {
		$file = $this->get_edge_case_file();

		// Lines 78-79: ends with period.
		$this->assert_no_error_on_line( $file, 78 );
		$this->assert_no_error_on_line( $file, 79 );
	}

	/**
	 * Test total error count on edge case fixture.
	 *
	 * 9 AppendPeriod (lines 4, 6, 8, 60, 62, 64, 68, 71, 76)
	 * + 6 InvalidEndChar (lines 10, 12, 14, 16, 18, 20)
	 * = 15 total.
	 *
	 * @return void
	 */
	public function test_edge_case_total_error_count(): void {
		$file = $this->get_edge_case_file();

		$this->assertSame( 15, $file->getErrorCount() );
	}

	/**
	 * Test fixer output on edge case fixture.
	 *
	 * Only the 9 AppendPeriod violations get periods appended.
	 * The 6 InvalidEndChar violations are left unchanged.
	 *
	 * @return void
	 */
	public function test_edge_case_fixer(): void {
		$file = $this->get_edge_case_file();

		$fixed    = $this->get_fixed_content( $file );
		$expected = file_get_contents( $this->get_fixture_path( 'inline-comment-period-edge-cases.inc.fixed' ) );

		$this->assertSame( $expected, $fixed );
	}

	// -------------------------------------------------------------------
	// Configurable extra closers (inline-comment-period-config.inc).
	// -------------------------------------------------------------------

	/**
	 * Helper: config fixture path.
	 *
	 * @return string
	 */
	private function get_config_fixture(): string {
		return $this->get_fixture_path( 'inline-comment-period-config.inc' );
	}

	/**
	 * Test baseline: no extra properties — all invalid endings flagged.
	 *
	 * Config fixture errors (no properties):
	 *   Line 4  (:)  InvalidEndChar
	 *   Line 6  (;)  InvalidEndChar
	 *   Line 8  (])  InvalidEndChar
	 *   Line 10 ())  InvalidEndChar
	 *   Line 12 (🚀) InvalidEndChar
	 *   Line 14 (∴)  InvalidEndChar
	 *   Line 16 (8)  InvalidEndChar
	 *   Line 22 (r)  AppendPeriod
	 *   Total: 8 errors.
	 *
	 * @return void
	 */
	public function test_config_baseline_no_extras(): void {
		$file = $this->check_file( $this->get_config_fixture(), self::SNIFF );

		$this->assertSame( 8, $file->getErrorCount() );

		$this->assert_error_code_on_line( $file, 4, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 6, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 8, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 10, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 12, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 14, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 16, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 22, self::APPEND_PERIOD );
	}

	/**
	 * Test extra_accepted_closers: literal characters suppress errors.
	 *
	 * With :;)] configured, lines 4, 6, 8, 10 become accepted.
	 * Remaining: 12 (🚀), 14 (∴), 16 (8) InvalidEndChar + 22 AppendPeriod = 4.
	 *
	 * @return void
	 */
	public function test_config_literal_closers(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_closers' => ':;)]' )
		);

		$this->assertSame( 4, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 4 );
		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 8 );
		$this->assert_no_error_on_line( $file, 10 );

		$this->assert_error_code_on_line( $file, 12, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 14, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 16, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 22, self::APPEND_PERIOD );
	}

	/**
	 * Test extra_accepted_pattern: Unicode property classes suppress errors.
	 *
	 * With \p{So} (Symbol, Other), line 12 (🚀) becomes accepted.
	 * Remaining: 7 errors.
	 *
	 * @return void
	 */
	public function test_config_pattern_symbol_other(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_pattern' => '\p{So}' )
		);

		$this->assertSame( 7, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 12 );
		$this->assert_error_code_on_line( $file, 14, self::INVALID_END );
	}

	/**
	 * Test extra_accepted_pattern with multiple classes.
	 *
	 * With \p{So}\p{Sm}, both 🚀 (So) and ∴ (Sm) become accepted.
	 * Remaining: 6 errors.
	 *
	 * @return void
	 */
	public function test_config_pattern_multiple_classes(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_pattern' => '\p{So}\p{Sm}' )
		);

		$this->assertSame( 6, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 12 );
		$this->assert_no_error_on_line( $file, 14 );
	}

	/**
	 * Test extra_accepted_hex_ranges: specific codepoint ranges.
	 *
	 * 🚀 is U+1F680. Range 0x1F300-0x1F6FF covers it.
	 * Remaining: 7 errors.
	 *
	 * @return void
	 */
	public function test_config_hex_ranges(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_hex_ranges' => '0x1F300-0x1F6FF' )
		);

		$this->assertSame( 7, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 12 );
	}

	/**
	 * Test all three properties combined.
	 *
	 * Literal :;)] + pattern \p{So}\p{Sm} + hex for digits.
	 * Only line 16 (digit 8) and line 22 (letter) remain.
	 *
	 * @return void
	 */
	public function test_config_all_combined(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array(
				'extra_accepted_closers'    => ':;)]',
				'extra_accepted_pattern'    => '\p{So}\p{Sm}',
			)
		);

		$this->assertSame( 2, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 4 );
		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 8 );
		$this->assert_no_error_on_line( $file, 10 );
		$this->assert_no_error_on_line( $file, 12 );
		$this->assert_no_error_on_line( $file, 14 );

		$this->assert_error_code_on_line( $file, 16, self::INVALID_END );
		$this->assert_error_code_on_line( $file, 22, self::APPEND_PERIOD );
	}

	/**
	 * Test permissive pattern: \p{P} accepts any punctuation.
	 *
	 * All punctuation endings (:, ;, ], )) are accepted.
	 * Emoji (🚀) and math (∴) are NOT \p{P}. Digit 8 is not either.
	 * Remaining: 3 InvalidEndChar (12, 14, 16) + 1 AppendPeriod (22) = 4.
	 *
	 * @return void
	 */
	public function test_config_pattern_any_punctuation(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_pattern' => '\p{P}' )
		);

		$this->assertSame( 4, $file->getErrorCount() );

		$this->assert_no_error_on_line( $file, 4 );
		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 8 );
		$this->assert_no_error_on_line( $file, 10 );
	}

	/**
	 * Test that valid closers and non-error lines are unaffected by extra config.
	 *
	 * @return void
	 */
	public function test_config_valid_endings_still_clean(): void {
		$file = $this->check_file(
			$this->get_config_fixture(),
			self::SNIFF,
			array( 'extra_accepted_closers' => ':;)]' )
		);

		// Lines 18, 20: . and ! are always valid.
		$this->assert_no_error_on_line( $file, 18 );
		$this->assert_no_error_on_line( $file, 20 );
	}
}
