<?php

namespace Emrikol\Tests\PHP;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.PHP.StrictTypes sniff.
 *
 * @package Emrikol\Tests\PHP
 */
class StrictTypesSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.PHP.StrictTypes';

	/**
	 * The full error code emitted by the sniff.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.PHP.StrictTypes.MissingStrictTypes';

	/**
	 * A file with declare(strict_types=1) present should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_when_strict_types_present(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-present.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * A file with no declare statement at all should produce 1 fixable error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_strict_types_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * A file with declare(strict_types=0) should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_wrong_value(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-wrong-value.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * A file with a docblock before declare(strict_types=1) should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_with_docblock_before_declare(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-with-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * A file with a docblock but no declare statement should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_missing_with_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing-with-docblock.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * When fixing a file that has a docblock but no declare, the fixer should
	 * insert declare(strict_types=1); after the docblock closing tag.
	 *
	 * @return void
	 */
	public function test_fixable_inserts_after_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing-with-docblock.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// The declare should appear in the fixed output.
		$this->assertStringContainsString( 'declare(strict_types=1);', $fixed );

		// The docblock closing tag should appear before the declare statement.
		$docblock_close_pos = strpos( $fixed, '*/' );
		$declare_pos        = strpos( $fixed, 'declare(strict_types=1);' );

		$this->assertNotFalse( $docblock_close_pos, 'Fixed output should contain the docblock closing tag.' );
		$this->assertNotFalse( $declare_pos, 'Fixed output should contain declare(strict_types=1);.' );
		$this->assertGreaterThan(
			$docblock_close_pos,
			$declare_pos,
			'declare(strict_types=1); should appear after the docblock closing */.'
		);
	}

	/**
	 * A file with a different declare (not strict_types) should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_with_other_declare(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-other-declare.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * A file with only the opening PHP tag should produce 1 fixable error on line 1.
	 *
	 * @return void
	 */
	public function test_error_on_empty_file(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-empty.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * When fixing a file with no declare statement, the fixer should insert
	 * declare(strict_types=1); into the output.
	 *
	 * @return void
	 */
	public function test_fix_on_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( 'declare(strict_types=1);', $fixed );

		// The declare should appear near the top of the file, before the namespace.
		$declare_pos   = strpos( $fixed, 'declare(strict_types=1);' );
		$namespace_pos = strpos( $fixed, 'namespace MyApp' );

		$this->assertNotFalse( $declare_pos, 'Fixed output should contain declare statement.' );
		$this->assertNotFalse( $namespace_pos, 'Fixed output should still contain namespace.' );
		$this->assertLessThan(
			$namespace_pos,
			$declare_pos,
			'declare(strict_types=1); should appear before the namespace declaration.'
		);
	}

	/**
	 * A file with declare(strict_types=0) should still have the wrong value
	 * after fixing — the fixer only handles the missing-declaration case,
	 * not the wrong-value case.
	 *
	 * @return void
	 */
	public function test_wrong_value_not_auto_fixed(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-wrong-value.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// The fixer should not change the existing declaration.
		$this->assertStringContainsString( 'strict_types=0', $fixed );
	}

	/**
	 * A file with a different declare (not strict_types) should retain its
	 * original declare after fixing — the fixer inserts a new strict_types
	 * declaration but does not modify the existing one.
	 *
	 * @return void
	 */
	public function test_other_declare_preserved_after_fix(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-other-declare.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// The original declare should still be present.
		$this->assertStringContainsString( "declare(encoding='UTF-8')", $fixed );
	}

	/**
	 * A file with phpcs:disable directives inside the docblock and
	 * declare(strict_types=1) present should produce no errors.
	 *
	 * Regression: T_PHPCS_DISABLE tokens inside docblocks must be
	 * skipped when scanning for the declare statement.
	 *
	 * @return void
	 */
	public function test_no_error_with_phpcs_disable_in_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-present-with-phpcs-disable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * A file with phpcs:disable directives inside the docblock but no
	 * declare statement should produce 1 fixable error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_missing_with_phpcs_disable_in_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing-with-phpcs-disable.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * When fixing a file that has phpcs:disable in the docblock, the fixer
	 * should insert exactly one declare(strict_types=1); after the docblock.
	 *
	 * Regression: The fixer must not enter an infinite loop (FAILED TO FIX)
	 * because T_PHPCS_DISABLE tokens inside the docblock caused findNext()
	 * to stop before reaching the T_DECLARE token on subsequent passes.
	 *
	 * @return void
	 */
	public function test_fixer_converges_with_phpcs_disable_in_docblock(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'strict-types-missing-with-phpcs-disable.inc' ),
			self::SNIFF_CODE
		);

		$fixed = $this->get_fixed_content( $file );

		// The declare must appear exactly once.
		$count = substr_count( $fixed, 'declare(strict_types=1);' );
		$this->assertSame(
			1,
			$count,
			"Expected exactly 1 declare(strict_types=1); in fixed output, found {$count}. "
			. 'The fixer likely entered an infinite loop due to T_PHPCS_DISABLE tokens in the docblock.'
		);

		// The declare should appear after the docblock closing tag.
		$docblock_close_pos = strpos( $fixed, '*/' );
		$declare_pos        = strpos( $fixed, 'declare(strict_types=1);' );

		$this->assertNotFalse( $docblock_close_pos );
		$this->assertNotFalse( $declare_pos );
		$this->assertGreaterThan(
			$docblock_close_pos,
			$declare_pos,
			'declare(strict_types=1); should appear after the docblock closing */.'
		);
	}
}
