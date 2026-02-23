<?php
/**
 * Emrikol Coding Standards.
 *
 * Tests for Emrikol.Comments.DocblockTypeSync sniff.
 *
 * @package Emrikol\Tests\Comments
 */

namespace Emrikol\Tests\Comments;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Class DocblockTypeSyncSniffTest
 *
 * Tests for the DocblockTypeSyncSniff, which ensures docblocks match
 * code-side type declarations.
 */
class DocblockTypeSyncSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Comments.DocblockTypeSync';

	/**
	 * Error code for missing type on @param tag.
	 *
	 * @var string
	 */
	private const MISSING_PARAM_TYPE = 'Emrikol.Comments.DocblockTypeSync.MissingParamType';

	/**
	 * Error code for missing @param tag.
	 *
	 * @var string
	 */
	private const MISSING_PARAM_TAG = 'Emrikol.Comments.DocblockTypeSync.MissingParamTag';

	/**
	 * Error code for missing type on @return tag.
	 *
	 * @var string
	 */
	private const MISSING_RETURN_TYPE = 'Emrikol.Comments.DocblockTypeSync.MissingReturnType';

	/**
	 * Error code for missing @return tag.
	 *
	 * @var string
	 */
	private const MISSING_RETURN_TAG = 'Emrikol.Comments.DocblockTypeSync.MissingReturnTag';

	/**
	 * Error code for missing docblock entirely.
	 *
	 * @var string
	 */
	private const MISSING_DOCBLOCK = 'Emrikol.Comments.DocblockTypeSync.MissingDocblock';

	/**
	 * Warning code for type drift.
	 *
	 * @var string
	 */
	private const TYPE_DRIFT = 'Emrikol.Comments.DocblockTypeSync.TypeDrift';

	// =========================================================================
	// Correct fixture — no errors or warnings expected.
	// =========================================================================

	/**
	 * A file with all correct docblocks should produce no errors or warnings.
	 *
	 * @return void
	 */
	public function test_no_errors_on_correct_file(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
		$this->assert_no_warnings( $file );
	}

	/**
	 * PHPDoc type aliases (boolean, integer, double, real, callback) should
	 * be treated as equivalent to their canonical PHP types and produce no
	 * TypeDrift warnings.
	 *
	 * @return void
	 */
	public function test_no_drift_on_phpdoc_aliases(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
		$this->assert_no_warnings( $file );
	}

	// =========================================================================
	// Empty type fixture — MissingParamType, MissingReturnType.
	// =========================================================================

	/**
	 * A @param tag with no type should produce MissingParamType.
	 *
	 * @return void
	 */
	public function test_empty_param_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 7, self::MISSING_PARAM_TYPE );
	}

	/**
	 * After fixing, the @param tag should have the type prepended while
	 * preserving the original description text.
	 *
	 * @return void
	 */
	public function test_fix_empty_param_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $name The name.', $fixed );
	}

	/**
	 * A bare @return tag with no type should produce MissingReturnType.
	 *
	 * @return void
	 */
	public function test_empty_return_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 20, self::MISSING_RETURN_TYPE );
	}

	/**
	 * After fixing, the bare @return tag should have the type added.
	 *
	 * @return void
	 */
	public function test_fix_empty_return_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@return string', $fixed );
	}

	/**
	 * Both param and return empty types should produce errors on their
	 * respective lines.
	 *
	 * @return void
	 */
	public function test_both_empty_types(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 29, self::MISSING_PARAM_TYPE );
		$this->assert_error_code_on_line( $file, 30, self::MISSING_PARAM_TYPE );
		$this->assert_error_code_on_line( $file, 32, self::MISSING_RETURN_TYPE );
	}

	/**
	 * Both param and return types should be filled after fix.
	 *
	 * @return void
	 */
	public function test_fix_both_empty_types(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $name The name.', $fixed );
		$this->assertStringContainsString( '@param int $count The count.', $fixed );
		$this->assertStringContainsString( '@return bool', $fixed );
	}

	/**
	 * Nullable param with empty type should be fixed with Type|null notation.
	 *
	 * @return void
	 */
	public function test_fix_nullable_empty_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string|null $value The value.', $fixed );
	}

	/**
	 * Extra whitespace around the $variable in @param should still be parsed
	 * and the type should be added correctly.
	 *
	 * @return void
	 */
	public function test_extra_whitespace_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 51, self::MISSING_PARAM_TYPE );
	}

	/**
	 * Exact error count on empty-type fixture for regression detection.
	 *
	 * @return void
	 */
	public function test_exact_error_count_empty_type(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 7, $file->getErrorCount(), 'Empty-type fixture should have exactly 7 errors.' );
		$this->assertSame( 0, $file->getWarningCount(), 'Empty-type fixture should have 0 warnings.' );
	}

	// =========================================================================
	// Missing tag fixture — MissingParamTag, MissingReturnTag.
	// =========================================================================

	/**
	 * A typed parameter with no @param tag should produce MissingParamTag.
	 *
	 * @return void
	 */
	public function test_missing_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 9, self::MISSING_PARAM_TAG );
	}

	/**
	 * After fixing, a @param tag should be inserted into the existing docblock.
	 *
	 * @return void
	 */
	public function test_fix_missing_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $name', $fixed );
	}

	/**
	 * A typed return with no @return tag should produce MissingReturnTag.
	 *
	 * @return void
	 */
	public function test_missing_return_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 18, self::MISSING_RETURN_TAG );
	}

	/**
	 * After fixing, a @return tag should be inserted.
	 *
	 * @return void
	 */
	public function test_fix_missing_return_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@return string', $fixed );
	}

	/**
	 * Missing both @param and @return tags should produce both errors on the
	 * function declaration line.
	 *
	 * @return void
	 */
	public function test_missing_both_tags(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 25, self::MISSING_PARAM_TAG );
		$this->assert_error_code_on_line( $file, 25, self::MISSING_RETURN_TAG );
	}

	/**
	 * Has one @param but missing the second should produce MissingParamTag
	 * only for the undocumented parameter.
	 *
	 * @return void
	 */
	public function test_missing_second_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 34, self::MISSING_PARAM_TAG );
	}

	/**
	 * After fixing missing second param, the inserted tag should appear.
	 *
	 * @return void
	 */
	public function test_fix_missing_second_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param int $count', $fixed );
	}

	/**
	 * Missing @return with existing @param tags should produce MissingReturnTag.
	 *
	 * @return void
	 */
	public function test_missing_return_with_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 43, self::MISSING_RETURN_TAG );
	}

	/**
	 * Missing @param for nullable typed parameter should produce MissingParamTag.
	 *
	 * @return void
	 */
	public function test_missing_nullable_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 52, self::MISSING_PARAM_TAG );
	}

	/**
	 * After fixing, the nullable param should be inserted as Type|null.
	 *
	 * @return void
	 */
	public function test_fix_missing_nullable_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string|null $value', $fixed );
	}

	/**
	 * Exact error count on missing-tag fixture for regression detection.
	 *
	 * @return void
	 */
	public function test_exact_error_count_missing_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-missing-tag.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 9, $file->getErrorCount(), 'Missing-tag fixture should have exactly 9 errors.' );
		$this->assertSame( 0, $file->getWarningCount(), 'Missing-tag fixture should have 0 warnings.' );
	}

	// =========================================================================
	// No docblock fixture — MissingDocblock.
	// =========================================================================

	/**
	 * A function with typed params and no docblock should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_missing_docblock_typed_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 4, self::MISSING_DOCBLOCK );
	}

	/**
	 * A function with typed return and no docblock should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_missing_docblock_typed_return(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 7, self::MISSING_DOCBLOCK );
	}

	/**
	 * After fixing, a full docblock should be generated with @param and
	 * placeholder description.
	 *
	 * @return void
	 */
	public function test_fix_generates_docblock_with_param(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $name', $fixed );
		$this->assertStringContainsString( '[Description placeholder.]', $fixed );
	}

	/**
	 * Generated docblock should include @return tag for typed return.
	 *
	 * @return void
	 */
	public function test_fix_generates_return_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@return string', $fixed );
	}

	/**
	 * With generate_missing_docblocks=false, no MissingDocblock should be
	 * emitted at all.
	 *
	 * @return void
	 */
	public function test_no_docblock_generation_when_disabled(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE,
			array( 'generate_missing_docblocks' => false )
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Class method without docblock should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_missing_docblock_class_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 17, self::MISSING_DOCBLOCK );
	}

	/**
	 * Static class method without docblock should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_missing_docblock_static_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 21, self::MISSING_DOCBLOCK );
	}

	/**
	 * Nullable types should be converted to Type|null in generated docblocks.
	 *
	 * @return void
	 */
	public function test_fix_generates_nullable_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string|null $value', $fixed );
		$this->assertStringContainsString( '@return int|null', $fixed );
	}

	/**
	 * Union types should appear as-is in generated docblocks.
	 *
	 * @return void
	 */
	public function test_fix_generates_union_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param int|string $value', $fixed );
	}

	/**
	 * A function with only void return should still get a generated docblock
	 * with @return void.
	 *
	 * @return void
	 */
	public function test_missing_docblock_void_only(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 32, self::MISSING_DOCBLOCK );
	}

	/**
	 * Generated docblock for multiline parameters should align types.
	 *
	 * @return void
	 */
	public function test_fix_generates_aligned_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Both param and return should be in the same generated docblock.
		$this->assertStringContainsString( '@param string $name', $fixed );
		$this->assertStringContainsString( '@param int    $count', $fixed );
		$this->assertStringContainsString( '@return bool', $fixed );
	}

	/**
	 * Exact error count on no-docblock fixture for regression detection.
	 *
	 * @return void
	 */
	public function test_exact_error_count_no_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-no-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 8, $file->getErrorCount(), 'No-docblock fixture should have exactly 8 errors.' );
		$this->assertSame( 0, $file->getWarningCount(), 'No-docblock fixture should have 0 warnings.' );
	}

	// =========================================================================
	// Type drift fixture — TypeDrift warnings.
	// =========================================================================

	/**
	 * Parameter type drift should produce a TypeDrift warning.
	 *
	 * @return void
	 */
	public function test_param_type_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_code_on_line( $file, 7, self::TYPE_DRIFT );
	}

	/**
	 * Return type drift should produce a TypeDrift warning.
	 *
	 * @return void
	 */
	public function test_return_type_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_code_on_line( $file, 20, self::TYPE_DRIFT );
	}

	/**
	 * Both param and return type drift should produce warnings.
	 *
	 * @return void
	 */
	public function test_both_type_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_code_on_line( $file, 29, self::TYPE_DRIFT );
		$this->assert_warning_code_on_line( $file, 30, self::TYPE_DRIFT );
		$this->assert_warning_code_on_line( $file, 32, self::TYPE_DRIFT );
	}

	/**
	 * Nullable type drift (?string vs int|null) should produce a warning.
	 *
	 * @return void
	 */
	public function test_nullable_type_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_code_on_line( $file, 41, self::TYPE_DRIFT );
	}

	/**
	 * Self vs string return type drift should produce a warning.
	 *
	 * @return void
	 */
	public function test_self_return_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_code_on_line( $file, 53, self::TYPE_DRIFT );
	}

	/**
	 * TypeDrift should NOT be auto-fixable. The fixer should not modify the file.
	 *
	 * @return void
	 */
	public function test_type_drift_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 0, $file->getFixableCount(), 'TypeDrift warnings should not be fixable.' );
	}

	/**
	 * With report_type_drift=false, no TypeDrift warnings should be emitted
	 * but errors on other fixtures would still fire.
	 *
	 * @return void
	 */
	public function test_no_drift_when_disabled(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE,
			array( 'report_type_drift' => false )
		);

		$this->assert_no_warnings( $file );
	}

	/**
	 * With report_type_drift=false, MissingParamType errors should still be
	 * reported (drift is disabled, not other checks).
	 *
	 * @return void
	 */
	public function test_drift_disabled_still_reports_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE,
			array( 'report_type_drift' => false )
		);

		$this->assertSame( 7, $file->getErrorCount(), 'Disabling drift should not suppress errors.' );
	}

	/**
	 * Exact error and warning counts on type-drift fixture for regression.
	 *
	 * @return void
	 */
	public function test_exact_counts_type_drift(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-type-drift.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 0, $file->getErrorCount(), 'Type-drift fixture should have 0 errors.' );
		$this->assertSame( 10, $file->getWarningCount(), 'Type-drift fixture should have exactly 10 warnings.' );
	}

	// =========================================================================
	// More specific fixture — valid specializations, no errors/warnings.
	// =========================================================================

	/**
	 * Valid type specializations should produce no errors or warnings.
	 *
	 * @return void
	 */
	public function test_specializations_no_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-more-specific.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
		$this->assert_no_warnings( $file );
	}

	// =========================================================================
	// Edge cases fixture.
	// =========================================================================

	/**
	 * Constructor @param tags should be processed normally — no errors when
	 * all typed params are documented.
	 *
	 * @return void
	 */
	public function test_constructor_params_checked(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 11 );
	}

	/**
	 * Constructor, destructor, and clone should not require @return tag.
	 *
	 * @return void
	 */
	public function test_constructor_no_return_needed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$codes = $this->get_error_codes_by_line( $file );
		foreach ( array( 11, 18, 23 ) as $line ) {
			$line_codes = $codes[ $line ] ?? array();
			$this->assertNotContains(
				self::MISSING_RETURN_TAG,
				$line_codes,
				"__construct/__destruct/__clone should not require @return tag (line {$line})."
			);
		}
	}

	/**
	 * {@inheritdoc} docblocks should be skipped entirely.
	 *
	 * @return void
	 */
	public function test_inheritdoc_skipped(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 58 );
	}

	/**
	 * {@INHERITDOC} (uppercase) should also be skipped.
	 *
	 * @return void
	 */
	public function test_inheritdoc_uppercase_skipped(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 64 );
	}

	/**
	 * Abstract methods should be processed normally (no errors when correct).
	 *
	 * @return void
	 */
	public function test_abstract_method_processed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 38 );
	}

	/**
	 * Interface methods should be processed normally (no errors when correct).
	 *
	 * @return void
	 */
	public function test_interface_method_processed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 48 );
	}

	/**
	 * Fully untyped functions should produce no errors.
	 *
	 * @return void
	 */
	public function test_fully_untyped_no_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 92 );
	}

	/**
	 * Functions with extra tags (@since, @throws) should not be disturbed.
	 *
	 * @return void
	 */
	public function test_extra_tags_not_disturbed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 106 );
	}

	/**
	 * Constructor with missing @param tags should produce MissingParamTag.
	 *
	 * @return void
	 */
	public function test_constructor_missing_param_tag(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 120, self::MISSING_PARAM_TAG );
	}

	/**
	 * After fixing constructor with missing params, both @param tags should
	 * be inserted.
	 *
	 * @return void
	 */
	public function test_fix_constructor_missing_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $name', $fixed );
		$this->assertStringContainsString( '@param int $age', $fixed );
	}

	/**
	 * Constructor promotion should be processed like normal params.
	 *
	 * @return void
	 */
	public function test_constructor_promotion_no_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// PromotionClass __construct has correct docblock — no errors.
		$this->assert_no_error_on_line( $file, 83 );
	}

	/**
	 * Function right after a closure should NOT pick up the closure as its
	 * docblock target — it should get MissingDocblock.
	 *
	 * @return void
	 */
	public function test_function_after_closure_missing_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// after_closure() on line 132.
		$this->assert_error_code_on_line( $file, 132, self::MISSING_DOCBLOCK );
	}

	/**
	 * Multiline function signature should get a generated docblock.
	 *
	 * @return void
	 */
	public function test_multiline_no_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 136, self::MISSING_DOCBLOCK );
	}

	/**
	 * After fixing multiline signature, the generated docblock should have
	 * all three param tags.
	 *
	 * @return void
	 */
	public function test_fix_multiline_generates_all_params(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '@param string $first', $fixed );
		$this->assertStringContainsString( '@param int', $fixed );
		$this->assertStringContainsString( '@param bool', $fixed );
	}

	/**
	 * By-reference parameter with correct docblock should not error.
	 *
	 * @return void
	 */
	public function test_by_reference_no_error(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// by_reference() has correct docblock.
		$this->assert_no_error_on_line( $file, 153 );
	}

	/**
	 * Class docblock should NOT be treated as a function docblock — a method
	 * inside the class should still get MissingDocblock.
	 *
	 * @return void
	 */
	public function test_class_docblock_not_confused(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// method_no_doc inside ClassWithDocblock.
		$this->assert_error_code_on_line( $file, 162, self::MISSING_DOCBLOCK );
	}

	/**
	 * Interleaved functions: first and third have correct docblocks, second
	 * has no docblock. No docblock crosstalk should occur.
	 *
	 * @return void
	 */
	public function test_no_docblock_crosstalk(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		// sequence_first — correct, no errors.
		$this->assert_no_error_on_line( $file, 172 );
		// sequence_second_no_doc — MissingDocblock.
		$this->assert_error_code_on_line( $file, 178, self::MISSING_DOCBLOCK );
		// sequence_third — correct, no errors.
		$this->assert_no_error_on_line( $file, 186 );
	}

	/**
	 * Exact error count on edge-cases fixture for regression detection.
	 *
	 * @return void
	 */
	public function test_exact_error_count_edge_cases(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 6, $file->getErrorCount(), 'Edge-cases fixture should have exactly 6 errors.' );
		$this->assertSame( 0, $file->getWarningCount(), 'Edge-cases fixture should have 0 warnings.' );
	}

	// =========================================================================
	// Configuration property — generate_missing_docblocks=false still fixes
	// existing docblocks.
	// =========================================================================

	/**
	 * With generate_missing_docblocks=false, errors in existing docblocks
	 * (MissingParamType, etc.) should still be reported.
	 *
	 * @return void
	 */
	public function test_disabled_generation_still_fixes_existing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-empty-type.inc' ),
			self::SNIFF_CODE,
			array( 'generate_missing_docblocks' => false )
		);

		// Empty types in existing docblocks should still error.
		$this->assertSame( 7, $file->getErrorCount(), 'Disabling generation should not suppress existing docblock errors.' );
	}

	// =========================================================================
	// Inline phpcs:ignore suppression.
	// =========================================================================

	/**
	 * Inline phpcs:ignore with correct sniff code should suppress the error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_correct_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
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
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 8, 1 );
		$this->assert_error_code_on_line( $file, 8, self::MISSING_DOCBLOCK );
	}

	/**
	 * Inline phpcs:ignore with note separator should still suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_with_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
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
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 14 );
	}

	/**
	 * Unsuppressed function should still error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_no_suppress(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
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
			$this->get_fixture_path( 'docblock-sync-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Inline suppression fixture should have exactly 2 errors.' );
	}

	// =========================================================================
	// Comment-between fixture — phpcs directives between docblock and function.
	// =========================================================================

	/**
	 * Case 1: phpcs:ignore between docblock and function should not
	 * break docblock association.
	 *
	 * @return void
	 */
	public function test_phpcs_ignore_between_docblock_and_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 15 );
	}

	/**
	 * Case 2: Inline phpcs:ignore on function line should not affect
	 * docblock detection.
	 *
	 * @return void
	 */
	public function test_inline_phpcs_ignore_no_effect_on_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 28 );
	}

	/**
	 * Case 3: phpcs:disable between docblock and function should not
	 * break docblock association.
	 *
	 * @return void
	 */
	public function test_phpcs_disable_between_docblock_and_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 42 );
	}

	/**
	 * Case 4: A regular (non-phpcs) comment between docblock and function
	 * should break the association. MissingDocblock expected.
	 *
	 * @return void
	 */
	public function test_regular_comment_breaks_docblock_association(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 59, self::MISSING_DOCBLOCK );
	}

	/**
	 * Case 5: Multiple stacked phpcs:ignore comments should all be
	 * transparent to docblock association.
	 *
	 * @return void
	 */
	public function test_multiple_phpcs_ignore_between(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 75 );
	}

	/**
	 * Case 6a: phpcs:ignore between docblock and class method declaration.
	 *
	 * @return void
	 */
	public function test_phpcs_ignore_before_class_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 91 );
	}

	/**
	 * Case 6b: phpcs:ignore between docblock and static class method.
	 *
	 * @return void
	 */
	public function test_phpcs_ignore_before_static_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 103 );
	}

	/**
	 * Case 7: No docblock at all, just phpcs:ignore before function.
	 * Should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_no_docblock_with_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 112, self::MISSING_DOCBLOCK );
	}

	/**
	 * Case 8: Complete docblock with phpcs:ignore between should produce
	 * no errors — the docblock is found through the directive.
	 *
	 * @return void
	 */
	public function test_complete_docblock_with_phpcs_ignore_between(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 126 );
	}

	/**
	 * Case 9: Incomplete docblock with phpcs:ignore between should produce
	 * MissingParamTag and MissingReturnTag, NOT MissingDocblock.
	 *
	 * @return void
	 */
	public function test_incomplete_docblock_with_phpcs_ignore_between(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 139, self::MISSING_PARAM_TAG );
		$this->assert_error_code_on_line( $file, 139, self::MISSING_RETURN_TAG );

		// Must NOT get MissingDocblock — the existing docblock should be found.
		$codes      = $this->get_error_codes_by_line( $file );
		$line_codes = $codes[139] ?? array();
		$this->assertNotContains(
			self::MISSING_DOCBLOCK,
			$line_codes,
			'Incomplete docblock with phpcs:ignore between should not produce MissingDocblock.'
		);
	}

	/**
	 * Case 10: Non-doc block comment between docblock and function should
	 * break association. MissingDocblock expected.
	 *
	 * @return void
	 */
	public function test_block_comment_breaks_docblock_association(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 155, self::MISSING_DOCBLOCK );
	}

	/**
	 * Case 11: phpcs:ignore for this specific sniff should suppress the error.
	 *
	 * @return void
	 */
	public function test_self_suppression_with_phpcs_ignore_above(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 160 );
	}

	/**
	 * Case 12: Existing docblock missing tags with phpcs:ignore between.
	 * Should get MissingParamTag and MissingReturnTag, NOT MissingDocblock.
	 *
	 * @return void
	 */
	public function test_existing_docblock_missing_tags_with_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 170, self::MISSING_PARAM_TAG );
		$this->assert_error_code_on_line( $file, 170, self::MISSING_RETURN_TAG );

		$codes      = $this->get_error_codes_by_line( $file );
		$line_codes = $codes[170] ?? array();
		$this->assertNotContains(
			self::MISSING_DOCBLOCK,
			$line_codes,
			'Existing docblock with missing tags should not produce MissingDocblock.'
		);
	}

	/**
	 * Case 13: phpcs:enable between docblock and function should not
	 * break docblock association.
	 *
	 * @return void
	 */
	public function test_phpcs_enable_between_docblock_and_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 186 );
	}

	/**
	 * Case 14: phpcs:ignore followed by a regular comment should break
	 * the docblock association. MissingDocblock expected.
	 *
	 * @return void
	 */
	public function test_directive_then_regular_comment_breaks_association(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 203, self::MISSING_DOCBLOCK );
	}

	/**
	 * Case 15: Regular comment followed by phpcs:ignore should break
	 * the docblock association. MissingDocblock expected.
	 *
	 * @return void
	 */
	public function test_regular_then_directive_comment_breaks_association(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 219, self::MISSING_DOCBLOCK );
	}

	/**
	 * Case 16: Blank line between phpcs:ignore and function should still
	 * allow docblock association through the directive.
	 *
	 * @return void
	 */
	public function test_blank_line_after_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 234 );
	}

	/**
	 * Case 17: Interface method with phpcs:ignore between docblock and
	 * declaration should not break association.
	 *
	 * @return void
	 */
	public function test_phpcs_ignore_before_interface_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 249 );
	}

	/**
	 * Case 18: Hash-style phpcs:ignore (# phpcs:ignore) between docblock
	 * and function should not break association.
	 *
	 * @return void
	 */
	public function test_hash_style_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 263 );
	}

	/**
	 * Case 19: Untyped function with phpcs:ignore should produce no
	 * errors — nothing for DocblockTypeSync to check.
	 *
	 * @return void
	 */
	public function test_untyped_with_phpcs_ignore_no_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 270 );
	}

	/**
	 * Case 20: Abstract method with phpcs:ignore between docblock and
	 * declaration should not break association.
	 *
	 * @return void
	 */
	public function test_phpcs_ignore_before_abstract_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 285 );
	}

	/**
	 * Case 21: Mixed phpcs:ignore and phpcs:disable stacked between
	 * docblock and function should all be transparent.
	 *
	 * @return void
	 */
	public function test_mixed_directive_types_between(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 300 );
	}

	/**
	 * Case 22: Empty docblock with phpcs:ignore between should be found.
	 * Should get MissingParamTag and MissingReturnTag, not MissingDocblock.
	 *
	 * @return void
	 */
	public function test_empty_docblock_with_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 311, self::MISSING_PARAM_TAG );
		$this->assert_error_code_on_line( $file, 311, self::MISSING_RETURN_TAG );

		$codes      = $this->get_error_codes_by_line( $file );
		$line_codes = $codes[311] ?? array();
		$this->assertNotContains(
			self::MISSING_DOCBLOCK,
			$line_codes,
			'Empty docblock with phpcs:ignore between should not produce MissingDocblock.'
		);
	}

	/**
	 * Case 23: No docblock with phpcs:ignore should produce MissingDocblock.
	 *
	 * @return void
	 */
	public function test_no_docblock_with_phpcs_ignore_fixer_target(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 319, self::MISSING_DOCBLOCK );
	}

	// =========================================================================
	// Comment-between fixer tests.
	// =========================================================================

	/**
	 * Fixer: Complete docblock with phpcs:ignore between should NOT
	 * generate a duplicate docblock.
	 *
	 * @return void
	 */
	public function test_fix_no_duplicate_docblock_with_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Extract only the content between the phpcs:ignore and the function.
		$ignore_marker = '// phpcs:ignore Some.Other.Sniff -- intentional suppression.';
		$func_marker   = 'function already_has_docblock';

		$ignore_pos = strpos( $fixed, $ignore_marker );
		$func_pos   = strpos( $fixed, $func_marker );

		$this->assertNotFalse( $ignore_pos, 'Fixed content should contain the phpcs:ignore marker.' );
		$this->assertNotFalse( $func_pos, 'Fixed content should contain already_has_docblock().' );

		// The region between the phpcs:ignore and the function must NOT contain
		// a generated docblock — the existing one above the phpcs:ignore suffices.
		$between = substr( $fixed, $ignore_pos + strlen( $ignore_marker ), $func_pos - $ignore_pos - strlen( $ignore_marker ) );

		$this->assertStringNotContainsString(
			'/**',
			$between,
			'Fixer must not insert a duplicate docblock between phpcs:ignore and already_has_docblock().'
		);
	}

	/**
	 * Fixer: Incomplete docblock with phpcs:ignore between should add
	 * tags to the existing docblock, not create a new one.
	 *
	 * @return void
	 */
	public function test_fix_adds_tags_to_existing_docblock_through_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Extract the region around incomplete_docblock().
		$func_pos = strpos( $fixed, 'function incomplete_docblock' );
		$region   = substr( $fixed, max( 0, $func_pos - 500 ), 500 );

		// The fixer bug generates a new docblock with [Description placeholder.]
		// instead of adding @param/@return to the existing "Method description." docblock.
		$this->assertStringNotContainsString(
			'[Description placeholder.]',
			$region,
			'Fixer must add tags to existing docblock, not create a new one for incomplete_docblock().'
		);

		// The existing description should still be present.
		$this->assertStringContainsString( 'Method description.', $region );
	}

	/**
	 * Fixer: When no docblock exists and phpcs:ignore is before the
	 * function, the generated docblock should appear BEFORE the
	 * phpcs:ignore comment, not between it and the function.
	 *
	 * @return void
	 */
	public function test_fix_inserts_docblock_before_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Find the function position (unique name).
		$func_pos = strpos( $fixed, 'function fixer_insert_before_ignore' );
		$this->assertNotFalse( $func_pos, 'Fixed content should contain fixer_insert_before_ignore().' );

		// Find the phpcs:ignore before it (unique sniff code).
		$ignore_pos = strrpos( substr( $fixed, 0, $func_pos ), '// phpcs:ignore Some.Sniff.ToKeep' );
		$this->assertNotFalse( $ignore_pos, 'Fixed content should still contain phpcs:ignore comment.' );

		// Find the docblock close before the function.
		$docblock_end = strrpos( substr( $fixed, 0, $func_pos ), '*/' );
		$this->assertNotFalse( $docblock_end, 'Fixed content should have a generated docblock before fixer_insert_before_ignore().' );

		// The docblock should come BEFORE the phpcs:ignore.
		$this->assertLessThan(
			$ignore_pos,
			$docblock_end,
			'Generated docblock should be inserted before the phpcs:ignore comment.'
		);
	}

	/**
	 * Fixer: Existing docblock missing tags with phpcs:ignore between
	 * should add tags to the existing docblock, not create a new one.
	 *
	 * @return void
	 */
	public function test_fix_existing_docblock_missing_tags_through_phpcs_ignore(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// Extract the region around existing_but_missing_tags().
		$func_pos = strpos( $fixed, 'function existing_but_missing_tags' );
		$region   = substr( $fixed, max( 0, $func_pos - 500 ), 500 );

		// The fixer bug generates a new docblock instead of adding to existing.
		$this->assertStringNotContainsString(
			'[Description placeholder.]',
			$region,
			'Fixer must add tags to existing docblock, not create a new one for existing_but_missing_tags().'
		);

		// The existing description should still be present.
		$this->assertStringContainsString( 'Process the data.', $region );
	}

	/**
	 * Exact error count on comment-between fixture for regression detection.
	 *
	 * @return void
	 */
	public function test_exact_error_count_comment_between(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'docblock-sync-comment-between.inc' ),
			self::SNIFF_CODE
		);

		// Expected errors after fix:
		// Line 59:  MissingDocblock (1)
		// Line 112: MissingDocblock (1)
		// Line 139: MissingParamTag x2 + MissingReturnTag (3)
		// Line 155: MissingDocblock (1)
		// Line 170: MissingParamTag + MissingReturnTag (2)
		// Line 203: MissingDocblock (1)
		// Line 219: MissingDocblock (1)
		// Line 311: MissingParamTag + MissingReturnTag (2)
		// Line 319: MissingDocblock (1)
		// Total: 13
		$this->assertSame( 13, $file->getErrorCount(), 'Comment-between fixture should have exactly 13 errors.' );
		$this->assertSame( 0, $file->getWarningCount(), 'Comment-between fixture should have 0 warnings.' );
	}
}
