<?php

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.Comments.PhpcsDirective sniff.
 *
 * @package Emrikol\Tests\Comments
 */
class PhpcsDirectiveSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Comments.PhpcsDirective';

	/**
	 * Error code for bare ignore directives.
	 *
	 * @var string
	 */
	private const BARE_IGNORE = 'Emrikol.Comments.PhpcsDirective.BareIgnore';

	/**
	 * Error code for bare disable directives.
	 *
	 * @var string
	 */
	private const BARE_DISABLE = 'Emrikol.Comments.PhpcsDirective.BareDisable';

	/**
	 * Error code for unmatched disable directives.
	 *
	 * @var string
	 */
	private const UNMATCHED_DISABLE = 'Emrikol.Comments.PhpcsDirective.UnmatchedDisable';

	/**
	 * Error code for ignore-file directives.
	 *
	 * @var string
	 */
	private const IGNORE_FILE = 'Emrikol.Comments.PhpcsDirective.IgnoreFile';

	/**
	 * Error code for deprecated ignore line.
	 *
	 * @var string
	 */
	private const DEPRECATED_IGNORE_LINE = 'Emrikol.Comments.PhpcsDirective.DeprecatedIgnoreLine';

	/**
	 * Error code for deprecated ignore start.
	 *
	 * @var string
	 */
	private const DEPRECATED_IGNORE_START = 'Emrikol.Comments.PhpcsDirective.DeprecatedIgnoreStart';

	/**
	 * Error code for deprecated ignore end.
	 *
	 * @var string
	 */
	private const DEPRECATED_IGNORE_END = 'Emrikol.Comments.PhpcsDirective.DeprecatedIgnoreEnd';

	/**
	 * Error code for missing note separator.
	 *
	 * @var string
	 */
	private const MISSING_NOTE_SEPARATOR = 'Emrikol.Comments.PhpcsDirective.MissingNoteSeparator';

	/**
	 * Error code for malformed note separator.
	 *
	 * @var string
	 */
	private const MALFORMED_NOTE_SEPARATOR = 'Emrikol.Comments.PhpcsDirective.MalformedNoteSeparator';

	/**
	 * Warning code for unmatched enable.
	 *
	 * @var string
	 */
	private const UNMATCHED_ENABLE = 'Emrikol.Comments.PhpcsDirective.UnmatchedEnable';

	// ---- Correct usage (no errors) ----

	/**
	 * Targeted ignores and matched pairs should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_on_correct_directives(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Repeated disable/enable pairs for the same sniff should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_on_repeated_pairs(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-repeated-pairs.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Bare directives ----

	/**
	 * Bare ignore and bare disable should produce errors.
	 *
	 * @return void
	 */
	public function test_error_on_bare_directives(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-bare-ignore.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Bare-ignore fixture should have exactly 2 errors.' );

		$this->assert_error_count_on_line( $file, 2, 1 );
		$this->assert_error_code_on_line( $file, 2, self::BARE_IGNORE );

		$this->assert_error_count_on_line( $file, 5, 1 );
		$this->assert_error_code_on_line( $file, 5, self::BARE_DISABLE );
	}

	/**
	 * Inline bare ignore at end of code line should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_inline_bare_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-inline.inc' ),
			self::SNIFF_CODE
		);

		// Line 4: inline targeted ignore — no error.
		$this->assert_no_error_on_line( $file, 4 );

		// Line 7: inline bare ignore — error.
		$this->assert_error_count_on_line( $file, 7, 1 );
		$this->assert_error_code_on_line( $file, 7, self::BARE_IGNORE );
	}

	/**
	 * Block comment syntax bare phpcs:ignore should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_block_comment_bare_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-inline.inc' ),
			self::SNIFF_CODE
		);

		// Line 10: block comment targeted — no error.
		$this->assert_no_error_on_line( $file, 10 );

		// Line 14: block comment bare — error.
		$this->assert_error_count_on_line( $file, 14, 1 );
		$this->assert_error_code_on_line( $file, 14, self::BARE_IGNORE );
	}

	/**
	 * Exact error count on inline fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_inline_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-inline.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Inline fixture should have exactly 2 errors.' );
	}

	// ---- Unmatched disables ----

	/**
	 * Single unmatched disable should produce an error.
	 *
	 * @return void
	 */
	public function test_error_on_unmatched_disable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount(), 'Unmatched fixture should have exactly 1 error.' );
		$this->assert_error_code_on_line( $file, 2, self::UNMATCHED_DISABLE );
	}

	/**
	 * Mixed pairs: matched disable/enable is OK, unmatched is error.
	 *
	 * @return void
	 */
	public function test_mixed_matched_and_unmatched(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-mixed-pairs.inc' ),
			self::SNIFF_CODE
		);

		// StrictTypes is matched — no error.
		$this->assert_no_error_on_line( $file, 2 );

		// TypeHinting is unmatched — error.
		$this->assert_error_count_on_line( $file, 6, 1 );
		$this->assert_error_code_on_line( $file, 6, self::UNMATCHED_DISABLE );

		$this->assertSame( 1, $file->getErrorCount() );
	}

	/**
	 * Multiple unmatched disables for different sniffs should each produce an error.
	 *
	 * @return void
	 */
	public function test_multiple_unmatched_disables(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 3, $file->getErrorCount(), 'Edge-cases fixture should have exactly 3 unmatched disable errors.' );

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::UNMATCHED_DISABLE );

		$this->assert_error_count_on_line( $file, 5, 1 );
		$this->assert_error_code_on_line( $file, 5, self::UNMATCHED_DISABLE );

		$this->assert_error_count_on_line( $file, 6, 1 );
		$this->assert_error_code_on_line( $file, 6, self::UNMATCHED_DISABLE );
	}

	// ---- Bare enable clears all ----

	/**
	 * Bare enable should clear all open disables.
	 *
	 * @return void
	 */
	public function test_bare_enable_clears_all(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-enable-all.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Suppression bypass ----

	/**
	 * Our BareIgnore error should resist suppression by phpcs:ignore
	 * targeting our own error code on the previous line.
	 *
	 * @return void
	 */
	public function test_suppression_bypass(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-suppression-bypass.inc' ),
			self::SNIFF_CODE
		);

		// Line 4: bare phpcs:ignore — error should be reported even though
		// line 3 has phpcs:ignore targeting our error code.
		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::BARE_IGNORE );
	}

	// ---- phpcs:ignoreFile ----

	/**
	 * phpcs:ignoreFile should be detected by our sniff.
	 *
	 * Note: PHPCS's File::process() normally intercepts phpcs:ignoreFile
	 * tokens and clears all errors before our sniff can preserve them.
	 * This test disables annotations so PHPCS doesn't intercept the token,
	 * verifying that our detection code works correctly.
	 *
	 * @return void
	 */
	public function test_error_on_ignore_file_directive(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-ignore-file.inc' ),
			self::SNIFF_CODE,
			array(),
			array( 'annotations' => false )
		);

		$this->assertSame( 1, $file->getErrorCount() );
		$this->assert_error_count_on_line( $file, 3, 1 );
		$this->assert_error_code_on_line( $file, 3, self::IGNORE_FILE );
	}

	// ---- Legacy directives ----

	/**
	 * Legacy directives should produce fixable errors on the correct lines.
	 *
	 * @return void
	 */
	public function test_error_on_legacy_directives(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-legacy.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 3, $file->getErrorCount(), 'Legacy fixture should have exactly 3 errors.' );
		$this->assertSame( 3, $file->getFixableCount(), 'All 3 legacy errors should be fixable.' );

		$this->assert_error_code_on_line( $file, 2, self::DEPRECATED_IGNORE_LINE );
		$this->assert_error_code_on_line( $file, 5, self::DEPRECATED_IGNORE_START );
		$this->assert_error_code_on_line( $file, 7, self::DEPRECATED_IGNORE_END );
	}

	/**
	 * Legacy directives should be auto-fixed to modern equivalents.
	 *
	 * @return void
	 */
	public function test_legacy_directives_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-legacy.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'phpcs:ignore', $fixed );
		$this->assertStringContainsString( 'phpcs:disable', $fixed );
		$this->assertStringContainsString( 'phpcs:enable', $fixed );
		$this->assertStringNotContainsString( '@codingStandardsIgnoreLine', $fixed );
		$this->assertStringNotContainsString( '@codingStandardsIgnoreStart', $fixed );
		$this->assertStringNotContainsString( '@codingStandardsIgnoreEnd', $fixed );
	}

	/**
	 * Legacy fix should preserve comment markers (// stays //).
	 *
	 * @return void
	 */
	public function test_legacy_fix_preserves_comment_markers(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-legacy.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Each replacement should still start with "// ".
		$this->assertStringContainsString( '// phpcs:ignore', $fixed );
		$this->assertStringContainsString( '// phpcs:disable', $fixed );
		$this->assertStringContainsString( '// phpcs:enable', $fixed );
	}

	// ---- Assumption tests: phpcs:set ----

	/**
	 * phpcs:set directives should NOT be flagged — they are inherently surgical.
	 *
	 * @return void
	 */
	public function test_no_error_on_phpcs_set(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-set.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Assumption tests: bare disable code path ----

	/**
	 * Bare phpcs:disable followed by targeted phpcs:enable should produce
	 * only the BareDisable error. The bare disable is not tracked in
	 * $open_disables, so no UnmatchedDisable should appear.
	 *
	 * @return void
	 */
	public function test_bare_disable_with_targeted_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-bare-disable-targeted-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount(), 'Should have exactly 1 error (BareDisable only).' );
		$this->assert_error_count_on_line( $file, 3, 1 );
		$this->assert_error_code_on_line( $file, 3, self::BARE_DISABLE );
	}

	// ---- Assumption tests: multi-code disable ----

	/**
	 * phpcs:disable with multiple sniff codes on one line should track
	 * each code independently. Enabling only some leaves others unmatched.
	 *
	 * @return void
	 */
	public function test_multi_code_disable_partial_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-multi-code-disable.inc' ),
			self::SNIFF_CODE
		);

		// Only Generic.Formatting.SpaceAfterCast should be unmatched.
		$this->assertSame( 1, $file->getErrorCount(), 'Should have exactly 1 unmatched disable error.' );
		$this->assert_error_count_on_line( $file, 3, 1 );
		$this->assert_error_code_on_line( $file, 3, self::UNMATCHED_DISABLE );
	}

	// ---- Assumption tests: legacy directives in block comments ----

	/**
	 * Legacy @codingStandardsIgnoreLine in block comment syntax should
	 * be detected and auto-fixable.
	 *
	 * @return void
	 */
	public function test_legacy_directive_in_block_comment(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-legacy-block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount() );
		$this->assertSame( 1, $file->getFixableCount() );
		$this->assert_error_count_on_line( $file, 3, 1 );
		$this->assert_error_code_on_line( $file, 3, self::DEPRECATED_IGNORE_LINE );
	}

	/**
	 * Legacy block comment fix should produce block comment output.
	 *
	 * @return void
	 */
	public function test_legacy_block_comment_fix_output(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-legacy-block-comment.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'phpcs:ignore', $fixed );
		$this->assertStringNotContainsString( '@codingStandardsIgnoreLine', $fixed );
		// Block comment markers should be preserved.
		$this->assertStringContainsString( '/* phpcs:ignore */', $fixed );
	}

	// ---- Real-world patterns ----

	/**
	 * Directive text inside strings and heredocs should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_directives_in_strings(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		// Lines 4-9 contain directive text in strings — no errors.
		$this->assert_no_error_on_line( $file, 4 );
		$this->assert_no_error_on_line( $file, 5 );
		$this->assert_no_error_on_line( $file, 7 );
		$this->assert_no_error_on_line( $file, 8 );
	}

	/**
	 * Nested disable/enable pairs for different sniffs should all match.
	 *
	 * @return void
	 */
	public function test_no_error_on_nested_disable_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 12 );
		$this->assert_no_error_on_line( $file, 13 );
		$this->assert_no_error_on_line( $file, 15 );
		$this->assert_no_error_on_line( $file, 16 );
	}

	/**
	 * Same sniff disabled twice then enabled should be considered matched.
	 *
	 * @return void
	 */
	public function test_no_error_on_double_disable_then_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 19 );
		$this->assert_no_error_on_line( $file, 21 );
		$this->assert_no_error_on_line( $file, 23 );
	}

	/**
	 * Enable for never-disabled sniff should NOT cause error.
	 *
	 * @return void
	 */
	public function test_no_error_on_enable_without_prior_disable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 26 );
	}

	/**
	 * Category-level disable with matching category-level enable should pass.
	 *
	 * @return void
	 */
	public function test_no_error_on_category_disable_with_matching_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 29 );
	}

	/**
	 * Exact error count on real-world no-error fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_real_world_no_error_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 0, $file->getErrorCount(), 'Real-world no-error fixture should have zero errors.' );
	}

	/**
	 * Category-level disable with only sub-sniff enable should flag unmatched.
	 *
	 * @return void
	 */
	public function test_error_on_category_disable_sub_sniff_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world-errors.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::UNMATCHED_DISABLE );
	}

	/**
	 * Legacy directive in docblock should be detected and auto-fixable.
	 *
	 * @return void
	 */
	public function test_error_on_legacy_directive_in_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world-errors.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::DEPRECATED_IGNORE_LINE );
	}

	/**
	 * Legacy directive with leading spaces should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_legacy_directive_with_leading_spaces(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world-errors.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 13, 1 );
		$this->assert_error_code_on_line( $file, 13, self::DEPRECATED_IGNORE_LINE );
	}

	/**
	 * Docblock legacy directive auto-fix should produce block comment.
	 *
	 * @return void
	 */
	public function test_docblock_legacy_fix_produces_block_comment(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world-errors.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// The docblock /** @codingStandardsIgnoreLine */ should become /* phpcs:ignore */.
		$this->assertStringContainsString( '/* phpcs:ignore */', $fixed );
		$this->assertStringNotContainsString( '/**', $fixed );
	}

	/**
	 * Exact error count on real-world errors fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_real_world_errors_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world-errors.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 3, $file->getErrorCount(), 'Real-world errors fixture should have exactly 3 errors.' );
	}

	// ---- Stress tests: complex patterns ----

	/**
	 * Interleaved disable/enable for different sniffs should all match.
	 *
	 * @return void
	 */
	public function test_no_error_on_interleaved_disable_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 7 );
		$this->assert_no_error_on_line( $file, 9 );
		$this->assert_no_error_on_line( $file, 11 );
	}

	/**
	 * Disable/enable cycling for same sniff should all match.
	 *
	 * @return void
	 */
	public function test_no_error_on_disable_enable_cycling(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 14 );
		$this->assert_no_error_on_line( $file, 16 );
		$this->assert_no_error_on_line( $file, 17 );
		$this->assert_no_error_on_line( $file, 19 );
	}

	/**
	 * Enable for never-disabled sniff should not error.
	 *
	 * @return void
	 */
	public function test_no_error_on_enable_never_disabled(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 22 );
	}

	/**
	 * Bare enable without prior disables should not error.
	 *
	 * @return void
	 */
	public function test_no_error_on_standalone_bare_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 25 );
	}

	/**
	 * Targeted ignore with multiple codes should not be flagged as bare.
	 *
	 * @return void
	 */
	public function test_no_error_on_multi_code_targeted_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 28 );
	}

	/**
	 * Disable with trailing comment text should not be flagged as bare.
	 *
	 * @return void
	 */
	public function test_no_error_on_disable_with_trailing_comment(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 32 );
	}

	/**
	 * Targeted disable cleared by bare enable should not be unmatched.
	 *
	 * @return void
	 */
	public function test_no_error_on_targeted_disable_cleared_by_bare_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 37 );
	}

	/**
	 * Legacy directives in heredoc should NOT be detected.
	 *
	 * @return void
	 */
	public function test_no_error_on_legacy_in_heredoc(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 43 );
	}

	/**
	 * Legacy directives in nowdoc should NOT be detected.
	 *
	 * @return void
	 */
	public function test_no_error_on_legacy_in_nowdoc(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 50 );
	}

	/**
	 * Multiple consecutive bare ignores should each be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_multiple_consecutive_bare_ignores(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 58, 1 );
		$this->assert_error_code_on_line( $file, 58, self::BARE_IGNORE );

		$this->assert_error_count_on_line( $file, 59, 1 );
		$this->assert_error_code_on_line( $file, 59, self::BARE_IGNORE );
	}

	/**
	 * Bare disable cleared by bare enable should produce BareDisable
	 * but no UnmatchedDisable.
	 *
	 * @return void
	 */
	public function test_error_on_bare_disable_cleared_by_bare_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 63, 1 );
		$this->assert_error_code_on_line( $file, 63, self::BARE_DISABLE );
	}

	/**
	 * Multi-code disable with partial enable should leave unmatched code.
	 *
	 * @return void
	 */
	public function test_error_on_partial_enable_unmatched_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 68, 1 );
		$this->assert_error_code_on_line( $file, 68, self::UNMATCHED_DISABLE );
	}

	/**
	 * Hash-style legacy directive should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_hash_style_legacy_directive(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 73, 1 );
		$this->assert_error_code_on_line( $file, 73, self::DEPRECATED_IGNORE_LINE );
	}

	/**
	 * Suppression bypass: bare ignore should be reported even when
	 * previous line attempts to suppress with our own error code.
	 *
	 * @return void
	 */
	public function test_suppression_bypass_bare_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 79, 1 );
		$this->assert_error_code_on_line( $file, 79, self::BARE_IGNORE );
	}

	/**
	 * Suppression bypass: unmatched disable should be reported even when
	 * previous line attempts to suppress with our own error code.
	 *
	 * @return void
	 */
	public function test_suppression_bypass_unmatched_disable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 85, 1 );
		$this->assert_error_code_on_line( $file, 85, self::UNMATCHED_DISABLE );
	}

	/**
	 * Exact error count on stress fixture — 7 errors total.
	 *
	 * @return void
	 */
	public function test_exact_count_on_stress_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 7, $file->getErrorCount(), 'Stress fixture should have exactly 7 errors.' );
	}

	/**
	 * Hash-style legacy directive should be auto-fixable.
	 *
	 * @return void
	 */
	public function test_hash_style_legacy_is_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		// Only the hash-style legacy directive should be fixable.
		$this->assertSame( 1, $file->getFixableCount(), 'Only hash-style legacy directive should be fixable.' );

		$fixed = $this->get_fixed_content( $file );
		$this->assertStringContainsString( '# phpcs:ignore', $fixed );
		// Note: heredoc/nowdoc content still contains the legacy text as string data,
		// so we check the specific comment line was converted rather than global absence.
		$lines = explode( "\n", $fixed );
		$this->assertStringNotContainsString( '@codingStandardsIgnoreLine', $lines[72] );
	}

	// ---- Missing note separator tests ----

	/**
	 * Proper -- separator should not produce MissingNoteSeparator.
	 *
	 * @return void
	 */
	public function test_no_error_on_ignore_with_proper_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 7 );
		$this->assert_no_error_on_line( $file, 11 );
	}

	/**
	 * No note at all should not produce MissingNoteSeparator.
	 *
	 * @return void
	 */
	public function test_no_error_on_ignore_without_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 15 );
	}

	/**
	 * Disable/enable with proper -- separator should not error.
	 *
	 * @return void
	 */
	public function test_no_error_on_disable_enable_with_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 19 );
		$this->assert_no_error_on_line( $file, 21 );
	}

	/**
	 * Single code with note but no -- separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_single_code_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 26, 1 );
		$this->assert_error_code_on_line( $file, 26, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Multi-code with note but no -- separator should flag the last code.
	 *
	 * @return void
	 */
	public function test_error_on_multi_code_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 30, 1 );
		$this->assert_error_code_on_line( $file, 30, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * phpcs:disable with note but no -- separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_disable_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 34, 1 );
		$this->assert_error_code_on_line( $file, 34, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * phpcs:enable with note but no -- separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_enable_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 37, 1 );
		$this->assert_error_code_on_line( $file, 37, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Block comment with note but no -- separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_block_comment_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 40, 1 );
		$this->assert_error_code_on_line( $file, 40, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Hash comment with note but no -- separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_hash_comment_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 44, 1 );
		$this->assert_error_code_on_line( $file, 44, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Tab before note should be detected as missing separator.
	 *
	 * @return void
	 */
	public function test_error_on_tab_before_note_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 48, 1 );
		$this->assert_error_code_on_line( $file, 48, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Tab between codes (no comma) should be detected as missing separator.
	 *
	 * @return void
	 */
	public function test_error_on_tab_between_codes(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 52, 1 );
		$this->assert_error_code_on_line( $file, 52, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Multiple corrupted codes should flag but NOT be auto-fixable.
	 *
	 * @return void
	 */
	public function test_error_on_multi_corrupt_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 56, 1 );
		$this->assert_error_code_on_line( $file, 56, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Malformed -- without any spaces should flag as MalformedNoteSeparator.
	 *
	 * @return void
	 */
	public function test_error_on_malformed_separator_no_spaces(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 62, 1 );
		$this->assert_error_code_on_line( $file, 62, self::MALFORMED_NOTE_SEPARATOR );
	}

	/**
	 * Malformed -- with space after but not before should flag.
	 *
	 * @return void
	 */
	public function test_error_on_malformed_separator_space_after_only(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 66, 1 );
		$this->assert_error_code_on_line( $file, 66, self::MALFORMED_NOTE_SEPARATOR );
	}

	/**
	 * Inline ignore with missing separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_inline_missing_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 70, 1 );
		$this->assert_error_code_on_line( $file, 70, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Inline ignore with malformed separator should flag.
	 *
	 * @return void
	 */
	public function test_error_on_inline_malformed_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 73, 1 );
		$this->assert_error_code_on_line( $file, 73, self::MALFORMED_NOTE_SEPARATOR );
	}

	/**
	 * Exact error count on missing separator fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_missing_separator_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 13, $file->getErrorCount(), 'Missing separator fixture should have exactly 13 errors.' );
	}

	/**
	 * Single-corrupted-code errors should be fixable, multi-corrupted should not.
	 *
	 * @return void
	 */
	public function test_missing_separator_fixable_count(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 12, $file->getFixableCount(), '12 of 13 missing separator errors should be fixable.' );
	}

	/**
	 * Auto-fix should insert -- separator correctly.
	 *
	 * @return void
	 */
	public function test_missing_separator_fix_output(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-missing-separator.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Single-code fix: space replaced with ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Foo.Bar -- this note lacks the separator', $fixed );

		// Multi-code fix: last code's space replaced with ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Foo.Bar, Baz.Qux -- reason goes here', $fixed );

		// Disable fix.
		$this->assertStringContainsString( '// phpcs:disable Alpha.One -- temporarily disabling', $fixed );

		// Enable fix.
		$this->assertStringContainsString( '// phpcs:enable Alpha.One -- temporarily disabling', $fixed );

		// Block comment fix.
		$this->assertStringContainsString( '/* phpcs:ignore Gamma.Three -- some note here */', $fixed );

		// Hash comment fix.
		$this->assertStringContainsString( '# phpcs:ignore Delta.Four -- hash note', $fixed );

		// Tab-before-note fix: tab replaced with ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Epsilon.Five -- tab note here', $fixed );

		// Tab-between-codes fix: tab replaced with ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Zeta.Six -- Zeta.Seven', $fixed );

		// Multi-corrupt should NOT be fixed — original should remain.
		$this->assertStringContainsString( '// phpcs:ignore Eta.Eight note, Theta.Nine note2', $fixed );

		// Malformed no-spaces fix: normalize '--' to ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Iota.Ten -- note no spaces around dashes', $fixed );

		// Malformed space-after-only fix: normalize '--' to ' -- '.
		$this->assertStringContainsString( '// phpcs:ignore Kappa.Eleven -- note after only', $fixed );

		// Inline missing separator fix.
		$this->assertStringContainsString( '// phpcs:ignore Lambda.Twelve -- inline note here', $fixed );

		// Inline malformed separator fix.
		$this->assertStringContainsString( '// phpcs:ignore Mu.Thirteen -- inline note', $fixed );
	}

	// ---- Unmatched enable warning tests ----

	/**
	 * Matched disable/enable pairs should not produce UnmatchedEnable warning.
	 *
	 * @return void
	 */
	public function test_no_warning_on_matched_disable_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 9 );
	}

	/**
	 * Bare enable should not produce UnmatchedEnable warning.
	 *
	 * @return void
	 */
	public function test_no_warning_on_bare_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 14 );
	}

	/**
	 * Enable for never-disabled code with no active disables should warn.
	 * Uses raw content parsing path (sniffCodes NOT SET).
	 *
	 * @return void
	 */
	public function test_warning_on_enable_never_disabled_no_active(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 25, 1 );
		$this->assert_warning_code_on_line( $file, 25, self::UNMATCHED_ENABLE );
	}

	/**
	 * Enable for never-disabled code with another disable active should warn.
	 * Uses sniffCodes path (PHPCS populates sniffCodes when disables are active).
	 *
	 * @return void
	 */
	public function test_warning_on_enable_never_disabled_with_active(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 29, 1 );
		$this->assert_warning_code_on_line( $file, 29, self::UNMATCHED_ENABLE );
	}

	/**
	 * Sub-sniff enable when only category was disabled should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_sub_sniff_enable_for_category_disable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 35, 1 );
		$this->assert_warning_code_on_line( $file, 35, self::UNMATCHED_ENABLE );
	}

	/**
	 * Inline enable for never-disabled code should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_inline_unmatched_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 39, 1 );
		$this->assert_warning_code_on_line( $file, 39, self::UNMATCHED_ENABLE );
	}

	/**
	 * Multi-code enable with one matched and one unmatched should warn
	 * only for the unmatched code.
	 *
	 * @return void
	 */
	public function test_warning_on_partial_unmatched_multi_code_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 44, 1 );
		$this->assert_warning_code_on_line( $file, 44, self::UNMATCHED_ENABLE );
	}

	/**
	 * Exact warning count on unmatched enable fixture.
	 *
	 * @return void
	 */
	public function test_exact_warning_count_on_unmatched_enable_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 5, $file->getWarningCount(), 'Unmatched enable fixture should have exactly 5 warnings.' );
	}

	/**
	 * Unmatched enable fixture should have zero errors.
	 *
	 * @return void
	 */
	public function test_no_errors_on_unmatched_enable_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-unmatched-enable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Stress fixture: enable for never-disabled should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_stress_enable_never_disabled(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-stress.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 22, 1 );
		$this->assert_warning_code_on_line( $file, 22, self::UNMATCHED_ENABLE );
	}

	/**
	 * Real-world fixture: enable for never-disabled should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_real_world_enable_never_disabled(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 26, 1 );
		$this->assert_warning_code_on_line( $file, 26, self::UNMATCHED_ENABLE );
	}

	/**
	 * Real-world fixture: sub-sniff enable for category disable should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_real_world_sub_sniff_enable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 32, 1 );
		$this->assert_warning_code_on_line( $file, 32, self::UNMATCHED_ENABLE );
	}

	// ---- Cascading mismatch tests ----

	/**
	 * Clean disable + corrupted enable: UnmatchedDisable on the disable line.
	 *
	 * @return void
	 */
	public function test_cascading_clean_disable_corrupted_enable_unmatched(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 10, 1 );
		$this->assert_error_code_on_line( $file, 10, self::UNMATCHED_DISABLE );
	}

	/**
	 * Clean disable + corrupted enable: MissingNoteSeparator on the enable line.
	 *
	 * @return void
	 */
	public function test_cascading_clean_disable_corrupted_enable_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 12, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Clean disable + corrupted enable: UnmatchedEnable warning on the enable line.
	 *
	 * @return void
	 */
	public function test_cascading_clean_disable_corrupted_enable_warning(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 12, 1 );
		$this->assert_warning_code_on_line( $file, 12, self::UNMATCHED_ENABLE );
	}

	/**
	 * Corrupted disable + clean enable: MissingNoteSeparator on the disable line.
	 *
	 * @return void
	 */
	public function test_cascading_corrupted_disable_clean_enable_separator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 18, self::MISSING_NOTE_SEPARATOR );
	}

	/**
	 * Corrupted disable + clean enable: UnmatchedDisable on the disable line.
	 *
	 * @return void
	 */
	public function test_cascading_corrupted_disable_clean_enable_unmatched(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 18, self::UNMATCHED_DISABLE );
	}

	/**
	 * Corrupted disable + clean enable: UnmatchedEnable warning on the enable line.
	 *
	 * @return void
	 */
	public function test_cascading_corrupted_disable_clean_enable_warning(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 20, 1 );
		$this->assert_warning_code_on_line( $file, 20, self::UNMATCHED_ENABLE );
	}

	/**
	 * Both corrupted identically: MissingNoteSeparator on both lines, no UnmatchedDisable.
	 *
	 * @return void
	 */
	public function test_cascading_both_corrupted_identically_no_unmatched(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		// Both lines have MissingNoteSeparator.
		$this->assert_error_code_on_line( $file, 26, self::MISSING_NOTE_SEPARATOR );
		$this->assert_error_code_on_line( $file, 28, self::MISSING_NOTE_SEPARATOR );

		// Neither should have UnmatchedDisable — corrupted codes match.
		$error_codes_26 = $this->get_error_codes_by_line( $file );
		$this->assertNotContains(
			self::UNMATCHED_DISABLE,
			$error_codes_26[26] ?? array(),
			'Identically corrupted disable/enable should match (no UnmatchedDisable on line 26).'
		);
	}

	/**
	 * Exact error and warning counts on the cascading mismatch fixture.
	 *
	 * @return void
	 */
	public function test_exact_counts_on_cascading_mismatch_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'phpcs-directive-cascading-mismatch.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 6, $file->getErrorCount(), 'Cascading mismatch fixture should have exactly 6 errors.' );
		$this->assertSame( 2, $file->getWarningCount(), 'Cascading mismatch fixture should have exactly 2 warnings.' );
		$this->assertSame( 4, $file->getFixableCount(), 'Cascading mismatch fixture should have exactly 4 fixable errors.' );
	}
}
