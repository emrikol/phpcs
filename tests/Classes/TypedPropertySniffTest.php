<?php

namespace Emrikol\Tests\Classes;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.Classes.TypedProperty sniff.
 *
 * @package Emrikol\Tests\Classes
 */
class TypedPropertySniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Classes.TypedProperty';

	/**
	 * The full error code emitted by the sniff.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.Classes.TypedProperty.MissingPropertyType';

	/**
	 * A file with all properties typed should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_when_all_properties_typed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Properties without type declarations should produce errors,
	 * and the total count should be exact.
	 *
	 * @return void
	 */
	public function test_error_on_untyped_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 4, $file->getErrorCount(), 'Violations fixture should have exactly 4 errors.' );

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_count_on_line( $file, 5, 1 );
		$this->assert_error_count_on_line( $file, 6, 1 );
		$this->assert_error_count_on_line( $file, 7, 1 );

		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );
		$this->assert_error_code_on_line( $file, 5, self::ERROR_CODE );
		$this->assert_error_code_on_line( $file, 6, self::ERROR_CODE );
		$this->assert_error_code_on_line( $file, 7, self::ERROR_CODE );
	}

	/**
	 * Static properties without types should produce errors.
	 *
	 * @return void
	 */
	public function test_error_on_untyped_static_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );
	}

	/**
	 * Properties declared with 'var' keyword without types should produce errors.
	 *
	 * @return void
	 */
	public function test_error_on_var_keyword_without_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 5, 1 );
		$this->assert_error_code_on_line( $file, 5, self::ERROR_CODE );
	}

	/**
	 * Untyped property with default value should produce an error.
	 *
	 * @return void
	 */
	public function test_error_on_untyped_with_default(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );
	}

	/**
	 * Typed properties (including readonly, static, nullable, union) should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_on_typed_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Typed regular properties.
		$this->assert_no_error_on_line( $file, 6 );
		$this->assert_no_error_on_line( $file, 7 );
		$this->assert_no_error_on_line( $file, 8 );

		// Nullable, union, simple typed.
		$this->assert_no_error_on_line( $file, 21 );
		$this->assert_no_error_on_line( $file, 22 );
		$this->assert_no_error_on_line( $file, 23 );
	}

	/**
	 * Constructor promotion parameters should be skipped
	 * (getMemberProperties throws RuntimeException for these).
	 *
	 * @return void
	 */
	public function test_skips_constructor_promotion(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 14 );
		$this->assert_no_error_on_line( $file, 15 );
	}

	/**
	 * Constants should never be flagged — they are not T_VARIABLE tokens.
	 *
	 * @return void
	 */
	public function test_no_error_on_constants(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 28 );
		$this->assert_no_error_on_line( $file, 29 );
		$this->assert_no_error_on_line( $file, 30 );
	}

	/**
	 * Local variables inside functions should not be flagged
	 * (getMemberProperties throws RuntimeException for non-property variables).
	 *
	 * @return void
	 */
	public function test_no_error_on_local_variables(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 35 );
		$this->assert_no_error_on_line( $file, 36 );
	}

	/**
	 * Untyped trait properties should produce errors; typed ones should not.
	 *
	 * @return void
	 */
	public function test_trait_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 41, 1 );
		$this->assert_error_code_on_line( $file, 41, self::ERROR_CODE );
		$this->assert_no_error_on_line( $file, 42 );
	}

	/**
	 * Anonymous class properties should be checked like regular class properties.
	 *
	 * @return void
	 */
	public function test_anonymous_class_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 47, 1 );
		$this->assert_error_code_on_line( $file, 47, self::ERROR_CODE );
		$this->assert_no_error_on_line( $file, 48 );
	}

	/**
	 * Intersection types are valid type declarations — no error.
	 *
	 * @return void
	 */
	public function test_no_error_on_intersection_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 53 );
	}

	/**
	 * PHP 8.4 asymmetric visibility: typed properties should pass,
	 * untyped should be flagged regardless of visibility modifiers.
	 *
	 * @return void
	 */
	public function test_asymmetric_visibility(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Typed with asymmetric visibility — no error.
		$this->assert_no_error_on_line( $file, 58 );
		$this->assert_no_error_on_line( $file, 60 );

		// Untyped with asymmetric visibility — error.
		$this->assert_error_count_on_line( $file, 59, 1 );
		$this->assert_error_code_on_line( $file, 59, self::ERROR_CODE );
		$this->assert_error_count_on_line( $file, 61, 1 );
		$this->assert_error_code_on_line( $file, 61, self::ERROR_CODE );
	}

	/**
	 * PHP 8.4 abstract properties: typed should pass, untyped should error.
	 *
	 * @return void
	 */
	public function test_abstract_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 66 );
		$this->assert_error_count_on_line( $file, 67, 1 );
		$this->assert_error_code_on_line( $file, 67, self::ERROR_CODE );
	}

	/**
	 * Multi-property declaration: type applies to all variables in the group.
	 * `public string $a, $b` means both have string type — no error.
	 *
	 * @return void
	 */
	public function test_no_error_on_multi_property_with_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 72 );
	}

	/**
	 * Enum cases should NOT be flagged as properties.
	 *
	 * @return void
	 */
	public function test_no_error_on_enum_cases(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// Regular enum cases.
		$this->assert_no_error_on_line( $file, 77 );
		$this->assert_no_error_on_line( $file, 78 );
		$this->assert_no_error_on_line( $file, 79 );

		// Backed enum cases.
		$this->assert_no_error_on_line( $file, 84 );
		$this->assert_no_error_on_line( $file, 85 );
	}

	/**
	 * Readonly class (PHP 8.2): typed properties pass, untyped still flagged.
	 *
	 * @return void
	 */
	public function test_readonly_class_properties(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 90 );
		$this->assert_error_count_on_line( $file, 91, 1 );
		$this->assert_error_code_on_line( $file, 91, self::ERROR_CODE );
	}

	/**
	 * DNF types (PHP 8.2) are valid type declarations — no error.
	 *
	 * @return void
	 */
	public function test_no_error_on_dnf_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 96 );
	}

	/**
	 * var keyword WITH type should not flag (type is present).
	 * var keyword WITHOUT type should flag.
	 *
	 * @return void
	 */
	public function test_var_keyword_with_and_without_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 101 );
		$this->assert_error_count_on_line( $file, 102, 1 );
		$this->assert_error_code_on_line( $file, 102, self::ERROR_CODE );
	}

	/**
	 * Verify exact total error count on edge-cases fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_total_error_count_on_edge_cases(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame(
			10,
			$file->getErrorCount(),
			'Edge-cases fixture should have exactly 10 errors.'
		);
	}

	// ---- Real-world patterns ----

	/**
	 * Properties with attributes should still flag if untyped.
	 *
	 * @return void
	 */
	public function test_error_on_attributed_untyped_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 6, 1 );
		$this->assert_error_code_on_line( $file, 6, self::ERROR_CODE );
		$this->assert_no_error_on_line( $file, 9 );
	}

	/**
	 * Properties with docblock only (no type) should still flag.
	 *
	 * @return void
	 */
	public function test_error_on_docblock_only_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 15, 1 );
		$this->assert_no_error_on_line( $file, 18 );
	}

	/**
	 * Multiple classes in one file should all be checked.
	 *
	 * @return void
	 */
	public function test_multiple_classes_in_file(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 29, 1 );
		$this->assert_no_error_on_line( $file, 30 );
		$this->assert_error_count_on_line( $file, 34, 1 );
		$this->assert_no_error_on_line( $file, 35 );
	}

	/**
	 * Nested anonymous class inside function should be checked.
	 *
	 * @return void
	 */
	public function test_nested_anonymous_class_in_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 41, 1 );
		$this->assert_no_error_on_line( $file, 42 );
	}

	/**
	 * Deeply nested anonymous class inside method should be checked.
	 *
	 * @return void
	 */
	public function test_deeply_nested_anonymous_class(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 48, 1 );
		$this->assert_error_count_on_line( $file, 52, 1 );
		$this->assert_no_error_on_line( $file, 53 );
	}

	/**
	 * Multi-line attribute on property should still flag if untyped.
	 *
	 * @return void
	 */
	public function test_multiline_attribute_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 64, 1 );
		$this->assert_no_error_on_line( $file, 67 );
	}

	/**
	 * Property after long docblock should still be flagged if untyped.
	 *
	 * @return void
	 */
	public function test_long_docblock_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 80, 1 );
		$this->assert_no_error_on_line( $file, 82 );
	}

	/**
	 * Static property with attribute should still flag if untyped.
	 *
	 * @return void
	 */
	public function test_static_attributed_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 88, 1 );
		$this->assert_no_error_on_line( $file, 91 );
	}

	/**
	 * Exact error count on real-world fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_real_world_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 11, $file->getErrorCount(), 'Real-world fixture should have exactly 11 errors.' );
	}

	// ---- PHP 8.4 property hooks ----

	/**
	 * Block-style property hooks: $this and $value inside hook bodies
	 * should NOT be flagged as untyped properties.
	 *
	 * @return void
	 */
	public function test_no_false_positive_in_block_hook_bodies(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// $this inside get hook body.
		$this->assert_no_error_on_line( $file, 8 );
		// $value in set param list and $this/$value in set body.
		$this->assert_no_error_on_line( $file, 9 );
	}

	/**
	 * Arrow-style property hooks: $this and $value should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_false_positive_in_arrow_hook_bodies(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// get => $this->short.
		$this->assert_no_error_on_line( $file, 29 );
		// set => ... $value.
		$this->assert_no_error_on_line( $file, 30 );
	}

	/**
	 * Untyped property with hooks should still be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_untyped_hooked_property(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 18, 1 );
		$this->assert_error_code_on_line( $file, 18, self::ERROR_CODE );
	}

	/**
	 * Properties declared after hooked properties should still be checked.
	 *
	 * @return void
	 */
	public function test_property_after_hooks_still_checked(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// Untyped property after a hooked property.
		$this->assert_error_count_on_line( $file, 50, 1 );
		$this->assert_error_code_on_line( $file, 50, self::ERROR_CODE );

		// Typed property after hooks — no error.
		$this->assert_no_error_on_line( $file, 53 );
	}

	/**
	 * Local-like variables inside hook bodies ($trimmed) should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_false_positive_on_local_vars_in_hook_body(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// $trimmed inside set hook body.
		$this->assert_no_error_on_line( $file, 58 );
		// $this inside set hook body.
		$this->assert_no_error_on_line( $file, 59 );
	}

	/**
	 * Nested anonymous class with hooks: both outer and inner hook bodies
	 * should be handled correctly.
	 *
	 * @return void
	 */
	public function test_nested_class_with_hooks(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		// Outer untyped property — flagged.
		$this->assert_error_count_on_line( $file, 68, 1 );
		$this->assert_error_code_on_line( $file, 68, self::ERROR_CODE );

		// Inner anonymous class: $this inside hook — NOT flagged.
		$this->assert_no_error_on_line( $file, 77 );

		// Inner anonymous class: untyped property — flagged.
		$this->assert_error_count_on_line( $file, 82, 1 );
		$this->assert_error_code_on_line( $file, 82, self::ERROR_CODE );
	}

	/**
	 * Exact error count on hooks fixture to catch any false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_hooks_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-hooks.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 4, $file->getErrorCount(), 'Hooks fixture should have exactly 4 errors.' );
	}

	// ---- Not auto-fixable ----

	/**
	 * The sniff should not produce fixable errors.
	 *
	 * @return void
	 */
	public function test_not_auto_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 0, $file->getFixableCount() );
	}

	// ---- Inline phpcs:ignore suppression ----

	/**
	 * Inline phpcs:ignore with correct sniff code should suppress the error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_correct_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 6 );
	}

	/**
	 * Inline phpcs:ignore with wrong sniff code should NOT suppress the error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_wrong_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );
	}

	/**
	 * Inline phpcs:ignore with note separator should still suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_with_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 12 );
	}

	/**
	 * Inline phpcs:ignore with parent sniff code should suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_parent_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 15 );
	}

	/**
	 * Unsuppressed property should still error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_no_suppress(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 18, 1 );
	}

	/**
	 * Exact error count on inline suppression fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_inline_suppression_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'typed-property-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Inline suppression fixture should have exactly 2 errors.' );
	}
}
