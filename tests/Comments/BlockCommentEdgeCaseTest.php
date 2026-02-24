<?php

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Edge case tests for the Emrikol.Comments.BlockComment sniff.
 *
 * Covers dangerous edge cases not handled by the happy-path test class:
 * - Comment text containing * / (would produce invalid PHP).
 * - Bare // lines with no text (Squiz parent deletes empty blocks).
 * - Unicode and extremely long comment lines.
 * - All declaration types (interface, trait, enum, const, final, static,
 *   abstract, readonly, var) for docblock detection.
 * - Non-declaration contexts (anonymous class, closure, variable).
 * - Isolated single-line comments and inline end-of-line comments.
 * - URLs containing // in comment text.
 * - PHP 8.0 attributes before declarations.
 * - Deeply nested indentation.
 * - Mixed indentation within a group.
 * - Comment text containing /* opening syntax.
 * - Hash + // on consecutive lines (multi-pass fixer).
 * - Middle bare // line in a group.
 * - Full fixer output comparison.
 */
class BlockCommentEdgeCaseTest extends BaseSniffTestCase {

	/**
	 * Sniff code for assertions.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Comments.BlockComment';

	/**
	 * Process the edge case fixture.
	 *
	 * @return \PHP_CodeSniffer\Files\LocalFile
	 */
	private function get_edge_file() {
		return $this->check_file(
			$this->get_fixture_path( 'block-comment-edge-cases.inc' ),
			self::SNIFF_CODE
		);
	}

	/**
	 * Test that comments containing star-slash are NOT flagged (would break PHP).
	 *
	 * @return void
	 */
	public function test_star_slash_in_text_skipped(): void {
		$file = $this->get_edge_file();

		$this->assert_no_error_on_line( $file, 3 );
		$this->assert_no_error_on_line( $file, 4 );
	}

	/**
	 * Test that bare // lines (no text) are NOT flagged.
	 *
	 * Converting bare // to a block comment produces an empty block that
	 * the Squiz parent deletes, causing an infinite fixer loop.
	 *
	 * @return void
	 */
	public function test_bare_slashes_skipped(): void {
		$file = $this->get_edge_file();

		$this->assert_no_error_on_line( $file, 7 );
		$this->assert_no_error_on_line( $file, 8 );
	}

	/**
	 * Test that Unicode content is flagged and converts correctly.
	 *
	 * @return void
	 */
	public function test_unicode_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 11, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that very long comment lines are flagged.
	 *
	 * @return void
	 */
	public function test_long_line_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 15, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before interface produces /** docblock.
	 *
	 * @return void
	 */
	public function test_before_interface_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 19, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before const (inside interface) produces /** docblock.
	 *
	 * @return void
	 */
	public function test_before_const_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 22, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before trait produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_trait_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 27, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before abstract method produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_abstract_method_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 30, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before final class produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_final_class_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 35, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before static method produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_static_method_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 38, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before enum produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_enum_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 45, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before anonymous class produces /* (not **).
	 *
	 * @return void
	 */
	public function test_before_anonymous_class_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 51, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before closure produces /* (not **).
	 *
	 * @return void
	 */
	public function test_before_closure_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 57, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before variable assignment produces /* (not **).
	 *
	 * @return void
	 */
	public function test_before_variable_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 63, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that isolated single // lines are NOT flagged.
	 *
	 * @return void
	 */
	public function test_isolated_singles_not_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_no_error_on_line( $file, 67 );
		$this->assert_no_error_on_line( $file, 69 );
		$this->assert_no_error_on_line( $file, 71 );
	}

	/**
	 * Test that inline end-of-line comments on consecutive lines are NOT flagged.
	 *
	 * @return void
	 */
	public function test_inline_comments_not_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_no_error_on_line( $file, 73 );
		$this->assert_no_error_on_line( $file, 74 );
		$this->assert_no_error_on_line( $file, 75 );
	}

	/**
	 * Test that comments with URLs containing // convert correctly.
	 *
	 * @return void
	 */
	public function test_url_in_comment_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 78, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before #[Attribute] + function produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_attributed_function_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 82, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test deeply nested comments (3 tabs) are flagged and indented correctly.
	 *
	 * @return void
	 */
	public function test_deeply_nested_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 90, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before readonly property produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_readonly_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 98, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that // before var property produces ** docblock.
	 *
	 * @return void
	 */
	public function test_before_var_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 108, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test that # on first line followed by // on second line works via multi-pass.
	 *
	 * Pass 1: # → //. Pass 2: // // → block comment.
	 *
	 * @return void
	 */
	public function test_hash_then_slash_hash_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 113, self::SNIFF_CODE . '.HashComment' );
	}

	/**
	 * Test that comments containing /* opening syntax are flagged and convert.
	 *
	 * Nested /* inside a block comment is harmless in PHP.
	 *
	 * @return void
	 */
	public function test_opening_syntax_in_text_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 117, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test mixed indentation within a group is flagged.
	 *
	 * @return void
	 */
	public function test_mixed_indentation_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 121, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test three-line group with middle bare // is flagged as one block.
	 *
	 * @return void
	 */
	public function test_middle_bare_line_flagged(): void {
		$file = $this->get_edge_file();

		$this->assert_error_code_on_line( $file, 125, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test the complete fixer output matches expected for all edge cases.
	 *
	 * @return void
	 */
	public function test_fixer_output(): void {
		$file = $this->get_edge_file();

		$fixed    = $this->get_fixed_content( $file );
		$expected = file_get_contents( $this->get_fixture_path( 'block-comment-edge-cases.inc.fixed' ) );

		$this->assertSame( $expected, $fixed );
	}

	/**
	 * Test that fixer output is valid PHP.
	 *
	 * @return void
	 */
	public function test_fixer_output_valid_php(): void {
		$file = $this->get_edge_file();

		$fixed = $this->get_fixed_content( $file );

		$temp = tempnam( sys_get_temp_dir(), 'phpcs_test_' ) . '.php';
		file_put_contents( $temp, $fixed );

		exec( 'php -l ' . escapeshellarg( $temp ) . ' 2>&1', $output, $exit_code );
		unlink( $temp );

		$this->assertSame( 0, $exit_code, 'Fixed output has PHP syntax errors: ' . implode( "\n", $output ) );
	}

	/**
	 * Test comment block at end of file with no trailing newline.
	 *
	 * @return void
	 */
	public function test_eof_comment_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-eof.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 3, self::SNIFF_CODE . '.WrongStyle' );
	}

	/**
	 * Test EOF fixer output matches expected.
	 *
	 * @return void
	 */
	public function test_eof_fixer_output(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'block-comment-eof.inc' ),
			self::SNIFF_CODE
		);

		$fixed    = $this->get_fixed_content( $file );
		$expected = file_get_contents( $this->get_fixture_path( 'block-comment-eof.inc.fixed' ) );

		$this->assertSame( $expected, $fixed );
	}
}
