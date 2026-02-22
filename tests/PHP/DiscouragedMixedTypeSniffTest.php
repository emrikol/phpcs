<?php

namespace Emrikol\Tests\PHP;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.PHP.DiscouragedMixedType sniff.
 *
 * @package Emrikol\Tests\PHP
 */
class DiscouragedMixedTypeSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.PHP.DiscouragedMixedType';

	/**
	 * Warning code for mixed parameter type.
	 *
	 * @var string
	 */
	private const MIXED_PARAM = 'Emrikol.PHP.DiscouragedMixedType.MixedParameterType';

	/**
	 * Warning code for mixed return type.
	 *
	 * @var string
	 */
	private const MIXED_RETURN = 'Emrikol.PHP.DiscouragedMixedType.MixedReturnType';

	/**
	 * Warning code for mixed property type.
	 *
	 * @var string
	 */
	private const MIXED_PROPERTY = 'Emrikol.PHP.DiscouragedMixedType.MixedPropertyType';

	/**
	 * A file with no mixed types should produce no warnings.
	 *
	 * @return void
	 */
	public function test_no_warning_when_no_mixed_types(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warnings( $file );
		$this->assert_no_errors( $file );
	}

	/**
	 * Mixed property type should produce exactly 1 warning.
	 *
	 * @return void
	 */
	public function test_warning_on_mixed_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 4, 1 );
		$this->assert_warning_code_on_line( $file, 4, self::MIXED_PROPERTY );
	}

	/**
	 * Mixed parameter and return types should produce exactly 2 warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_mixed_param_and_return(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 6, 2 );
		$this->assert_warning_code_on_line( $file, 6, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 6, self::MIXED_RETURN );
	}

	/**
	 * Only the mixed parameter should produce a warning when mixed with
	 * non-mixed params; the non-mixed return should not warn.
	 *
	 * @return void
	 */
	public function test_warning_only_on_mixed_param_not_string(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		// Line 10: string $name, mixed $value — exactly 1 warning (param only, void return).
		$this->assert_warning_count_on_line( $file, 10, 1 );
		$this->assert_warning_code_on_line( $file, 10, self::MIXED_PARAM );
	}

	/**
	 * mixed return type only (no mixed params) should produce exactly 1 warning.
	 *
	 * @return void
	 */
	public function test_warning_on_mixed_return_only(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		// Line 13: string $input, mixed return — exactly 1 warning (return only).
		$this->assert_warning_count_on_line( $file, 13, 1 );
		$this->assert_warning_code_on_line( $file, 13, self::MIXED_RETURN );
	}

	/**
	 * Multiple mixed params should each produce a separate warning.
	 *
	 * @return void
	 */
	public function test_warning_on_multiple_mixed_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		// Line 17: mixed $a, mixed $b — exactly 2 warnings (both params, void return).
		$this->assert_warning_count_on_line( $file, 17, 2 );
		$this->assert_warning_code_on_line( $file, 17, self::MIXED_PARAM );
	}

	/**
	 * Standalone function (not in a class) with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_standalone_function_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 22, 2 );
		$this->assert_warning_code_on_line( $file, 22, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 22, self::MIXED_RETURN );
	}

	/**
	 * Closures with mixed types should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_closure_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 27, 2 );
		$this->assert_warning_code_on_line( $file, 27, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 27, self::MIXED_RETURN );
	}

	/**
	 * Arrow functions with mixed types should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_arrow_function_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 32, 2 );
		$this->assert_warning_code_on_line( $file, 32, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 32, self::MIXED_RETURN );
	}

	/**
	 * Interface methods with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_interface_method_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 36, 2 );
		$this->assert_warning_code_on_line( $file, 36, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 36, self::MIXED_RETURN );
	}

	/**
	 * Abstract methods with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_abstract_method_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 41, 2 );
		$this->assert_warning_code_on_line( $file, 41, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 41, self::MIXED_RETURN );
	}

	/**
	 * Trait methods with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_trait_method_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 46, 2 );
		$this->assert_warning_code_on_line( $file, 46, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 46, self::MIXED_RETURN );
	}

	/**
	 * Local variables should NOT produce warnings — only properties.
	 *
	 * @return void
	 */
	public function test_no_warning_on_local_variables(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 53 );
		$this->assert_no_warning_on_line( $file, 54 );
	}

	/**
	 * Constructor promotion with mixed type should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_constructor_promotion_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		// Line 59: public mixed $promoted_mixed — warning.
		$this->assert_warning_count_on_line( $file, 59, 1 );
		$this->assert_warning_code_on_line( $file, 59, self::MIXED_PARAM );

		// Line 60: public string $promoted_string — no warning.
		$this->assert_no_warning_on_line( $file, 60 );
	}

	/**
	 * Variadic mixed parameter should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_variadic_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 66, 1 );
		$this->assert_warning_code_on_line( $file, 66, self::MIXED_PARAM );
	}

	/**
	 * Static methods with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_static_method_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 71, 2 );
		$this->assert_warning_code_on_line( $file, 71, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 71, self::MIXED_RETURN );
	}

	/**
	 * Readonly property with mixed type should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_readonly_mixed_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 78, 1 );
		$this->assert_warning_code_on_line( $file, 78, self::MIXED_PROPERTY );
	}

	/**
	 * Nested closure with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_nested_closure_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 83, 2 );
		$this->assert_warning_code_on_line( $file, 83, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 83, self::MIXED_RETURN );
	}

	// ---- Real-world patterns ----

	/**
	 * Closure with use() clause and mixed return type should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_closure_with_use_clause_mixed_return(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		// Line 4: mixed param + mixed return (through use clause).
		$this->assert_warning_count_on_line( $file, 4, 2 );
		$this->assert_warning_code_on_line( $file, 4, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 4, self::MIXED_RETURN );
	}

	/**
	 * Comment between colon and return type should still detect mixed.
	 *
	 * @return void
	 */
	public function test_warning_on_comment_before_return_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 9, 1 );
		$this->assert_warning_code_on_line( $file, 9, self::MIXED_RETURN );
	}

	/**
	 * Attribute on function parameter should still detect mixed.
	 *
	 * @return void
	 */
	public function test_warning_on_attributed_parameter(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 14, 1 );
		$this->assert_warning_code_on_line( $file, 14, self::MIXED_PARAM );
	}

	/**
	 * Attribute on function itself should still detect mixed param and return.
	 *
	 * @return void
	 */
	public function test_warning_on_attributed_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 19, 2 );
		$this->assert_warning_code_on_line( $file, 19, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 19, self::MIXED_RETURN );
	}

	/**
	 * Multiline parameter list with scattered mixed should detect all.
	 *
	 * @return void
	 */
	public function test_warning_on_multiline_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 24, 2 );
		$this->assert_warning_code_on_line( $file, 24, self::MIXED_PARAM );
	}

	/**
	 * Enum method with mixed should produce warnings.
	 *
	 * @return void
	 */
	public function test_warning_on_enum_method_mixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 36, 2 );
		$this->assert_warning_code_on_line( $file, 36, self::MIXED_PARAM );
		$this->assert_warning_code_on_line( $file, 36, self::MIXED_RETURN );
	}

	/**
	 * Closure with use clause and mixed param but no return type should warn param only.
	 *
	 * @return void
	 */
	public function test_warning_on_closure_use_param_only(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 45, 1 );
		$this->assert_warning_code_on_line( $file, 45, self::MIXED_PARAM );
	}

	/**
	 * Closure inside array value should still detect mixed.
	 *
	 * @return void
	 */
	public function test_warning_on_closure_in_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 51, 1 );
		$this->assert_warning_code_on_line( $file, 51, self::MIXED_PARAM );
	}

	/**
	 * Anonymous class method with mixed return should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_anonymous_class_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 58, 1 );
		$this->assert_warning_code_on_line( $file, 58, self::MIXED_RETURN );
	}

	/**
	 * Multiline return type with comment should still detect mixed.
	 *
	 * @return void
	 */
	public function test_warning_on_multiline_return_with_comment(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 64, 1 );
		$this->assert_warning_code_on_line( $file, 64, self::MIXED_RETURN );
	}

	/**
	 * Exact warning count on real-world fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_real_world_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 15, $file->getWarningCount(), 'Real-world fixture should have exactly 15 warnings.' );
	}

	// ---- PHP 8.4 property hook set parameters ----

	/**
	 * mixed in property hook set(mixed $value) should warn.
	 *
	 * @return void
	 */
	public function test_warning_on_hook_set_mixed_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// set(mixed $value) on typed property.
		$this->assert_warning_count_on_line( $file, 6, 1 );
		$this->assert_warning_code_on_line( $file, 6, self::MIXED_PARAM );
	}

	/**
	 * mixed property type and mixed set param should both warn.
	 *
	 * @return void
	 */
	public function test_warning_on_mixed_property_with_mixed_set_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// mixed property type.
		$this->assert_warning_count_on_line( $file, 11, 1 );
		$this->assert_warning_code_on_line( $file, 11, self::MIXED_PROPERTY );

		// set(mixed $value) on mixed property.
		$this->assert_warning_count_on_line( $file, 13, 1 );
		$this->assert_warning_code_on_line( $file, 13, self::MIXED_PARAM );
	}

	/**
	 * Non-mixed set parameter and untyped set should NOT warn.
	 *
	 * @return void
	 */
	public function test_no_warning_on_non_mixed_hook_set(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// set(string $value) — not mixed.
		$this->assert_no_warning_on_line( $file, 19 );

		// set($value) — no type at all.
		$this->assert_no_warning_on_line( $file, 24 );

		// Arrow hooks — no set parameter.
		$this->assert_no_warning_on_line( $file, 29 );
		$this->assert_no_warning_on_line( $file, 30 );
	}

	/**
	 * Exact warning count on property hooks fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_property_hooks_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 3, $file->getWarningCount(), 'Property hooks fixture should have exactly 3 warnings.' );
	}

	// ---- False positive checks ----

	/**
	 * Function named mixed() should NOT trigger a false positive.
	 * Property named $mixed with proper type should NOT trigger.
	 * String literals and comments containing "mixed" should NOT trigger.
	 *
	 * @return void
	 */
	public function test_no_false_positives_on_correct_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warnings( $file );
		$this->assert_no_errors( $file );
	}

	/**
	 * The sniff uses warnings, not errors — verify zero errors.
	 *
	 * @return void
	 */
	public function test_uses_warnings_not_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Verify exact total warning count to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_total_warning_count(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame(
			26,
			$file->getWarningCount(),
			'Violations fixture should have exactly 26 warnings.'
		);
	}

	// ---- Inline phpcs:ignore suppression ----

	/**
	 * Inline phpcs:ignore with correct sniff code should suppress the warning.
	 *
	 * @return void
	 */
	public function test_inline_suppression_correct_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 6 );
	}

	/**
	 * Inline phpcs:ignore with wrong sniff code should NOT suppress the warning.
	 *
	 * @return void
	 */
	public function test_inline_suppression_wrong_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 9, 1 );
		$this->assert_warning_code_on_line( $file, 9, self::MIXED_PROPERTY );
	}

	/**
	 * Inline phpcs:ignore with note separator should still suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_with_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 12 );
	}

	/**
	 * Inline phpcs:ignore with parent sniff code should suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_parent_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 15 );
	}

	/**
	 * Unsuppressed mixed property should still warn.
	 *
	 * @return void
	 */
	public function test_inline_suppression_no_suppress_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 18, 1 );
		$this->assert_warning_code_on_line( $file, 18, self::MIXED_PROPERTY );
	}

	/**
	 * Inline suppression on function with both mixed param and return.
	 *
	 * @return void
	 */
	public function test_inline_suppression_function_both(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 22 );
	}

	/**
	 * Wrong suppress code on function with mixed should still warn.
	 *
	 * @return void
	 */
	public function test_inline_suppression_function_wrong_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 25, 2 );
	}

	/**
	 * Exact warning count on inline suppression fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_inline_suppression_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'discouraged-mixed-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 4, $file->getWarningCount(), 'Inline suppression fixture should have exactly 4 warnings.' );
	}
}
