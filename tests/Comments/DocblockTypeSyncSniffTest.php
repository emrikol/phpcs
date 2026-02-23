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
		$this->assertSame( 8, $file->getWarningCount(), 'Type-drift fixture should have exactly 8 warnings.' );
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
}
