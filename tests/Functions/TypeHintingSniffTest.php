<?php
/**
 * Emrikol Coding Standards.
 *
 * Tests for Emrikol.Functions.TypeHinting sniff.
 *
 * @package Emrikol\Tests\Functions
 */

namespace Emrikol\Tests\Functions;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Class TypeHintingSniffTest
 *
 * Tests for the TypeHintingSniff, which requires all function parameters
 * to carry type hints and supports auto-fixing from @param docblocks.
 */
class TypeHintingSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Functions.TypeHinting';

	/**
	 * The full error code emitted by the sniff.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.Functions.TypeHinting.MissingParameterType';

	// -------------------------------------------------------------------------
	// Correct fixture — no errors expected.
	// -------------------------------------------------------------------------

	/**
	 * A file where every parameter already has a type hint should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_when_all_params_typed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// -------------------------------------------------------------------------
	// Missing fixture — bare errors with no docblock.
	// -------------------------------------------------------------------------

	/**
	 * A function with a single untyped parameter should produce 1 error on the
	 * function declaration line.
	 *
	 * @return void
	 */
	public function test_error_for_missing_single_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 7, 1 );
		$this->assert_error_code_on_line( $file, 7, self::ERROR_CODE );
	}

	/**
	 * A function with two untyped parameters should produce 2 errors on the
	 * function declaration line — one per parameter.
	 *
	 * @return void
	 */
	public function test_error_for_missing_multiple_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 12, 2 );
		$this->assert_error_code_on_line( $file, 12, self::ERROR_CODE );
	}

	/**
	 * A function with mixed typed and untyped parameters should produce 1 error
	 * (only for the untyped parameter).
	 *
	 * @return void
	 */
	public function test_error_for_mixed_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 17, 1 );
		$this->assert_error_code_on_line( $file, 17, self::ERROR_CODE );
	}

	/**
	 * An untyped parameter inside a class method should produce 1 error on the
	 * method declaration line.
	 *
	 * @return void
	 */
	public function test_error_in_class_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 21, 1 );
		$this->assert_error_code_on_line( $file, 21, self::ERROR_CODE );
	}

	// -------------------------------------------------------------------------
	// Docblock fixture — auto-fixable errors.
	// -------------------------------------------------------------------------

	/**
	 * A @param string docblock should make the error fixable, and the fixer
	 * should insert "string $name" into the parameter list.
	 *
	 * @return void
	 */
	public function test_fixable_with_string_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'string $name', $fixed );
	}

	/**
	 * The "integer" alias in a @param docblock should be normalised to "int"
	 * when the fixer runs.
	 *
	 * @return void
	 */
	public function test_alias_integer_to_int(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 16, 1 );
		$this->assert_error_code_on_line( $file, 16, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'int $count', $fixed );
	}

	/**
	 * The "boolean" alias in a @param docblock should be normalised to "bool"
	 * when the fixer runs.
	 *
	 * @return void
	 */
	public function test_alias_boolean_to_bool(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 22, 1 );
		$this->assert_error_code_on_line( $file, 22, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'bool $flag', $fixed );
	}

	/**
	 * A "Type|null" union in a @param docblock should be converted to a
	 * nullable hint "?Type" when the fixer runs.
	 *
	 * @return void
	 */
	public function test_nullable_union_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 28, 1 );
		$this->assert_error_code_on_line( $file, 28, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function nullable_param(?string $value)', $fixed );
	}

	/**
	 * A "null|Type" union (null first) in a @param docblock should also be
	 * converted to "?Type" when the fixer runs.
	 *
	 * @return void
	 */
	public function test_nullable_reversed_union_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 34, 1 );
		$this->assert_error_code_on_line( $file, 34, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function nullable_param_reversed(?string $value)', $fixed );
	}

	/**
	 * A "string[]" array notation in a @param docblock should be normalised
	 * to "array" when the fixer runs.
	 *
	 * @return void
	 */
	public function test_array_notation_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 40, 1 );
		$this->assert_error_code_on_line( $file, 40, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function with_array(array $items)', $fixed );
	}

	/**
	 * A generic "array<string>" notation in a @param docblock should be
	 * normalised to "array" when the fixer runs.
	 *
	 * @return void
	 */
	public function test_generic_array_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 46, 1 );
		$this->assert_error_code_on_line( $file, 46, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function with_generic_array(array $items)', $fixed );
	}

	/**
	 * A multi-type union such as "string|int" cannot be auto-fixed (PHP 7 does
	 * not support union type hints in the same way). The error should be present
	 * but the fixer should leave the parameter untyped in the output.
	 *
	 * @return void
	 */
	public function test_multi_union_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 52, 1 );
		$this->assert_error_code_on_line( $file, 52, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		// The parameter must remain untyped because the union is not auto-fixable.
		$this->assertStringContainsString( 'function multi_union($value)', $fixed );
	}

	/**
	 * A class name such as "WP_Post" in a @param docblock should be preserved
	 * verbatim as the type hint when the fixer runs.
	 *
	 * @return void
	 */
	public function test_class_name_preserved(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 58, 1 );
		$this->assert_error_code_on_line( $file, 58, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'WP_Post $post', $fixed );
	}

	// -------------------------------------------------------------------------
	// Edge-cases fixture.
	// -------------------------------------------------------------------------

	/**
	 * A pass-by-reference parameter with a @param string docblock should be
	 * fixed so the type hint precedes the & sigil: "string &$value".
	 *
	 * @return void
	 */
	public function test_by_reference_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Confirm an error exists for by_reference before fixing.
		$this->assert_error_code_on_line( $file, 10, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'string &$value', $fixed );
	}

	/**
	 * A variadic parameter with a @param string docblock should be fixed so
	 * the type hint precedes the ... sigil: "string ...$items".
	 *
	 * @return void
	 */
	public function test_variadic_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Confirm an error exists for variadic before fixing.
		$this->assert_error_code_on_line( $file, 16, self::ERROR_CODE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'string ...$items', $fixed );
	}

	/**
	 * A parameter with no docblock at all cannot be auto-fixed; the sniff
	 * should emit a plain (non-fixable) error on the function declaration line.
	 *
	 * @return void
	 */
	public function test_no_docblock_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 24, 1 );
		$this->assert_error_code_on_line( $file, 24, self::ERROR_CODE );

		// The parameter must remain untyped because there is no docblock to infer from.
		$fixed = $this->get_fixed_content( $file );
		$this->assertStringContainsString( 'function no_docblock_at_all($param)', $fixed );
	}

	/**
	 * A function with three untyped parameters backed by a full @param docblock
	 * should produce 3 errors and, after fixing, have all three parameters typed.
	 *
	 * @return void
	 */
	public function test_multi_params_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 33, 3 );

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'string $a', $fixed );
		$this->assertStringContainsString( 'int $b', $fixed );
		$this->assertStringContainsString( 'bool $c', $fixed );
	}

	/**
	 * A class method with untyped parameters and no docblock should produce
	 * errors — the sniff treats class methods the same as standalone functions.
	 *
	 * @return void
	 */
	public function test_class_method_untyped_params_no_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Container::bind has 2 untyped params and no docblock.
		$this->assert_error_count_on_line( $file, 37, 2 );
		$this->assert_error_code_on_line( $file, 37, self::ERROR_CODE );

		// Without a docblock, the fixer cannot add types.
		$fixed = $this->get_fixed_content( $file );
		$this->assertStringContainsString( 'function bind($abstract, $concrete)', $fixed );
	}

	// ---- Inline phpcs:ignore suppression ----

	/**
	 * Inline phpcs:ignore with correct sniff code should suppress the error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_correct_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 5 );
	}

	/**
	 * Inline phpcs:ignore with wrong sniff code should NOT suppress the error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_wrong_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 8, 1 );
		$this->assert_error_code_on_line( $file, 8, self::ERROR_CODE );
	}

	/**
	 * Inline phpcs:ignore with note separator should still suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_with_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 11 );
	}

	/**
	 * Inline phpcs:ignore with parent sniff code should suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_parent_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 14 );
	}

	/**
	 * Unsuppressed line should still error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_no_suppress(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 17, 1 );
	}

	/**
	 * Exact error count on inline suppression fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_inline_suppression_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-hinting-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Inline suppression fixture should have exactly 2 errors.' );
	}
}
