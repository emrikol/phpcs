<?php

namespace Emrikol\Tests\Functions;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.Functions.TypeDeclaration sniff.
 *
 * @package Emrikol\Tests\Functions
 */
class TypeDeclarationSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Functions.TypeDeclaration';

	/**
	 * The error code for a missing return type.
	 *
	 * @var string
	 */
	private const MISSING_RETURN_TYPE = 'Emrikol.Functions.TypeDeclaration.MissingReturnType';

	/**
	 * The error code for an invalid return type.
	 *
	 * @var string
	 */
	private const INVALID_RETURN_TYPE = 'Emrikol.Functions.TypeDeclaration.InvalidReturnType';

	// -------------------------------------------------------------------------
	// Basic presence checks
	// -------------------------------------------------------------------------

	/**
	 * A file where all functions have return type declarations should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_when_all_return_types_present(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Functions missing return type declarations should produce errors on the
	 * lines where the function keyword appears.
	 *
	 * @return void
	 */
	public function test_error_when_return_type_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 7, self::MISSING_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 12, self::MISSING_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 18, self::MISSING_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 23, self::MISSING_RETURN_TYPE );
	}

	/**
	 * Magic methods (__construct, __destruct, __clone) should be excluded from
	 * the return type requirement and produce no errors.
	 *
	 * @return void
	 */
	public function test_magic_methods_excluded(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 30 );
		$this->assert_no_error_on_line( $file, 31 );
		$this->assert_no_error_on_line( $file, 32 );
	}

	// -------------------------------------------------------------------------
	// Docblock auto-fix
	// -------------------------------------------------------------------------

	/**
	 * A function with "@return string" in its docblock should be auto-fixed
	 * with ": string" appended after the closing parenthesis.
	 *
	 * @return void
	 */
	public function test_fix_string_from_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_string(): string', $fixed );
	}

	/**
	 * "@return integer" (a docblock alias) should be fixed as ": int", not ": integer".
	 *
	 * @return void
	 */
	public function test_fix_integer_alias(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_integer(): int', $fixed );
		$this->assertStringNotContainsString( 'function returns_integer(): integer', $fixed );
	}

	/**
	 * "@return boolean" (a docblock alias) should be fixed as ": bool", not ": boolean".
	 *
	 * @return void
	 */
	public function test_fix_boolean_alias(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_boolean(): bool', $fixed );
		$this->assertStringNotContainsString( 'function returns_boolean(): boolean', $fixed );
	}

	/**
	 * "@return string|null" should be fixed as ": ?string".
	 *
	 * @return void
	 */
	public function test_fix_nullable_union(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_nullable(): ?string', $fixed );
	}

	/**
	 * "@return null|string" (null first) should also be fixed as ": ?string".
	 *
	 * @return void
	 */
	public function test_fix_nullable_reversed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_nullable_reversed(): ?string', $fixed );
	}

	/**
	 * "@return string[]" (array notation) should be fixed as ": array".
	 *
	 * @return void
	 */
	public function test_fix_array_notation(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_array_notation(): array', $fixed );
	}

	/**
	 * "@return array<string>" (generic array notation) should be fixed as ": array".
	 *
	 * @return void
	 */
	public function test_fix_generic_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_generic_array(): array', $fixed );
	}

	/**
	 * "@return ?string" (nullable shorthand in docblock) should be fixed as ": ?string".
	 *
	 * @return void
	 */
	public function test_fix_nullable_shorthand(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_nullable_shorthand(): ?string', $fixed );
	}

	/**
	 * "@return string|int" is a multi-type union that cannot be auto-fixed safely.
	 * An error should be reported but the fixer should not add a return type.
	 *
	 * @return void
	 */
	public function test_multi_union_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 65, self::MISSING_RETURN_TYPE );

		$fixed = $this->get_fixed_content( $file );

		// The fixer must not inject any return type for this function.
		$this->assertMatchesRegularExpression(
			'/function returns_multi_union\(\)\s*\{/',
			$fixed,
			'returns_multi_union should not have a return type added by the fixer.'
		);
	}

	/**
	 * "@return string|int|bool" (three-way union) cannot be auto-fixed.
	 * An error should be reported but the fixer should not add a return type.
	 *
	 * @return void
	 */
	public function test_triple_union_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 72, self::MISSING_RETURN_TYPE );

		$fixed = $this->get_fixed_content( $file );

		$this->assertMatchesRegularExpression(
			'/function returns_triple_union\(\)\s*\{/',
			$fixed,
			'returns_triple_union should not have a return type added by the fixer.'
		);
	}

	/**
	 * "@return WP_Post" (a class name) should be preserved as-is in the fix.
	 *
	 * @return void
	 */
	public function test_class_name_preserved_in_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'function returns_class_name(): WP_Post', $fixed );
	}

	// -------------------------------------------------------------------------
	// Type validation (validate_types)
	// -------------------------------------------------------------------------

	/**
	 * With default properties (validate_types=false), a file with all return
	 * types declared should produce no errors even if the types are unrecognised.
	 *
	 * @return void
	 */
	public function test_no_validation_by_default(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * With validate_types=true and auto_detect_php_classes=true, built-in PHP
	 * types and auto-detected PHP classes should pass. Only the unknown class
	 * lines should produce InvalidReturnType errors.
	 *
	 * @return void
	 */
	public function test_valid_builtin_types_pass_validation(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE,
			array(
				'validate_types'          => true,
				'auto_detect_php_classes' => true,
			)
		);

		// All primitive/built-in type lines should have no InvalidReturnType error.
		foreach ( range( 7, 24 ) as $line ) {
			$codes = $this->get_error_codes_by_line( $file )[ $line ] ?? array();
			$this->assertNotContains(
				self::INVALID_RETURN_TYPE,
				$codes,
				"Expected no InvalidReturnType error on line {$line}."
			);
		}

		// Unknown class lines should still error.
		$this->assert_error_code_on_line( $file, 34, self::INVALID_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 35, self::INVALID_RETURN_TYPE );
	}

	/**
	 * With validate_types=true, unknown class names in return types should
	 * produce InvalidReturnType errors.
	 *
	 * @return void
	 */
	public function test_unknown_class_fails_validation(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE,
			array(
				'validate_types' => true,
			)
		);

		$this->assert_error_code_on_line( $file, 34, self::INVALID_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 35, self::INVALID_RETURN_TYPE );
	}

	/**
	 * With validate_types=true and known_classes containing the unknown class
	 * names, those lines should NOT produce InvalidReturnType errors.
	 *
	 * @return void
	 */
	public function test_known_classes_pass_validation(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE,
			array(
				'validate_types' => true,
				'known_classes'  => array( 'FooBarBaz', 'SomeVendorClass' ),
			)
		);

		$this->assert_no_error_on_line( $file, 34 );
		$this->assert_no_error_on_line( $file, 35 );
	}

	/**
	 * With validate_types=true and auto_detect_php_classes=false and an empty
	 * known_classes list, even built-in PHP classes like DateTime should fail.
	 *
	 * @return void
	 */
	public function test_auto_detect_off_builtin_class_fails(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE,
			array(
				'validate_types'          => true,
				'auto_detect_php_classes' => false,
				'known_classes'           => array(),
			)
		);

		// Auto-detected built-in class lines should now error.
		$this->assert_error_code_on_line( $file, 27, self::INVALID_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 28, self::INVALID_RETURN_TYPE );

		// Unknown class lines should also error.
		$this->assert_error_code_on_line( $file, 34, self::INVALID_RETURN_TYPE );
		$this->assert_error_code_on_line( $file, 35, self::INVALID_RETURN_TYPE );
	}

	/**
	 * With validate_types=true and auto_detect_php_classes=true, a
	 * fully-qualified class name like \DateTime should pass (the leading
	 * backslash is stripped before validation).
	 *
	 * @return void
	 */
	public function test_fully_qualified_builtin_passes(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-validate-types.inc' ),
			self::SNIFF_CODE,
			array(
				'validate_types'          => true,
				'auto_detect_php_classes' => true,
			)
		);

		$this->assert_no_error_on_line( $file, 31 );
	}

	// -------------------------------------------------------------------------
	// Edge cases
	// -------------------------------------------------------------------------

	/**
	 * An abstract method without a return type declaration should still produce
	 * a MissingReturnType error.
	 *
	 * @return void
	 */
	public function test_abstract_method_needs_return_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 8, self::MISSING_RETURN_TYPE );
	}

	/**
	 * An interface method without a return type declaration should still produce
	 * a MissingReturnType error.
	 *
	 * @return void
	 */
	public function test_interface_method_needs_return_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 13, self::MISSING_RETURN_TYPE );
	}

	/**
	 * A method with all modifiers (final protected static) should still require
	 * a return type declaration.
	 *
	 * @return void
	 */
	public function test_method_with_modifiers(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 18, self::MISSING_RETURN_TYPE );
	}

	/**
	 * Closures (anonymous functions) are not flagged by the sniff because
	 * PHPCS tokenizes them as T_CLOSURE, not T_FUNCTION, and the sniff
	 * only registers for T_FUNCTION.
	 *
	 * @return void
	 */
	public function test_closure_not_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'type-declaration-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 24 );
	}
}
