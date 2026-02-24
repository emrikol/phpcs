<?php

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for the Emrikol.Comments.BlockComment sniff.
 *
 * Covers:
 * - WrongStyle: consecutive // → block comment conversion.
 * - HashComment: # → // conversion.
 * - Docblock detection: /** when before declarations.
 * - Configurable min_lines threshold.
 */
class BlockCommentSniffTest extends BaseSniffTestCase {

	/**
	 * Sniff code for assertions.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Comments.BlockComment';

	/**
	 * Test that two consecutive // lines are flagged as WrongStyle.
	 *
	 * @return void
	 */
	public function test_two_line_block_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 3, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that three consecutive // lines are flagged as WrongStyle.
	 *
	 * @return void
	 */
	public function test_three_line_block_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 7, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that a single // line is not flagged.
	 *
	 * @return void
	 */
	public function test_single_line_not_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 12 );
	}

	/**
	 * Test that inline end-of-line comments are not flagged.
	 *
	 * @return void
	 */
	public function test_inline_comment_not_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 15 );
	}

	/**
	 * Test that standalone // after inline is not grouped with it.
	 *
	 * @return void
	 */
	public function test_standalone_after_inline_not_grouped(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 16 );
	}

	/**
	 * Test that a block with a blank // line is flagged as one group.
	 *
	 * @return void
	 */
	public function test_block_with_blank_comment_line(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 19, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that a four-line // block is flagged.
	 *
	 * @return void
	 */
	public function test_four_line_block_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 25, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that tab-indented // blocks are flagged.
	 *
	 * @return void
	 */
	public function test_tab_indented_block_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 31, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // without space after slashes are flagged.
	 *
	 * @return void
	 */
	public function test_no_space_after_slashes_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 35, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that blank lines break groups.
	 *
	 * @return void
	 */
	public function test_blank_line_breaks_group(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		// Lines 39-40: 2-line group → flagged.
		$this->assert_error_code_on_line( $file, 39, self::SNIFF_CODE . '.WrongStyle' );

		// Line 42: single line after blank → not flagged.
		$this->assert_no_error_on_line( $file, 42 );
	}

	/**
	 * Test that # comments are flagged as HashComment.
	 *
	 * @return void
	 */
	public function test_hash_comment_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 45, self::SNIFF_CODE . '.HashComment' );
	}

	/**
	 * Test that consecutive # comments are each flagged as HashComment.
	 *
	 * @return void
	 */
	public function test_hash_multi_line_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 48, self::SNIFF_CODE . '.HashComment' );
		$this->assert_error_code_on_line( $file, 49, self::SNIFF_CODE . '.HashComment' );
	}

	/**
	 * Test that #text (no space) is flagged as HashComment.
	 *
	 * @return void
	 */
	public function test_hash_no_space_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 52, self::SNIFF_CODE . '.HashComment' );
	}

	/**
	 * Test that // blocks before functions produce /** in the fix.
	 *
	 * @return void
	 */
	public function test_before_function_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 55, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // blocks before classes produce /** in the fix.
	 *
	 * @return void
	 */
	public function test_before_class_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 61, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // blocks before methods produce /** in the fix.
	 *
	 * @return void
	 */
	public function test_before_method_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 64, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // blocks before properties produce /** in the fix.
	 *
	 * @return void
	 */
	public function test_before_property_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 70, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test the complete fixer output matches expected.
	 *
	 * @return void
	 */
	public function test_fixer_output(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment.inc' ),
			self::SNIFF_CODE
		);

		$fixed    = $this->get_fixed_content( $file );
		$expected = file_get_contents( $this->get_fixture_path( 'block-comment.inc.fixed' ) );

		$this->assertSame( $expected, $fixed );
	}

	/**
	 * Test min_lines=3 skips 2-line blocks.
	 *
	 * @return void
	 */
	public function test_min_lines_config(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-config.inc' ),
			self::SNIFF_CODE,
			array( 'min_lines' => 3 )
		);

		// 2-line block on line 3 — NOT flagged.
		$this->assert_no_error_on_line( $file, 3 );

		// 3-line block on line 7 — flagged.
		$this->assert_error_code_on_line( $file, 7, self::SNIFF_CODE . '.WrongStyle' );
	}
}
