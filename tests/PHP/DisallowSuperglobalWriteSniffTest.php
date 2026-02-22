<?php

namespace Emrikol\Tests\PHP;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.PHP.DisallowSuperglobalWrite sniff.
 *
 * @package Emrikol\Tests\PHP
 */
class DisallowSuperglobalWriteSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.PHP.DisallowSuperglobalWrite';

	/**
	 * The full error code for superglobal write.
	 *
	 * @var string
	 */
	private const WRITE_ERROR = 'Emrikol.PHP.DisallowSuperglobalWrite.SuperglobalWrite';

	/**
	 * The full error code for superglobal unset.
	 *
	 * @var string
	 */
	private const UNSET_ERROR = 'Emrikol.PHP.DisallowSuperglobalWrite.SuperglobalUnset';

	// ---- Correct usage (no errors) ----

	/**
	 * Reading superglobals should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_on_reads(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Direct assignments ----

	/**
	 * Writing to $_GET should produce an error.
	 *
	 * @return void
	 */
	public function test_error_get_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 5, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_POST should produce an error.
	 *
	 * @return void
	 */
	public function test_error_post_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 8, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_REQUEST should produce an error.
	 *
	 * @return void
	 */
	public function test_error_request_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 11, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_SERVER should produce an error.
	 *
	 * @return void
	 */
	public function test_error_server_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 14, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_COOKIE should produce an error.
	 *
	 * @return void
	 */
	public function test_error_cookie_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 17, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_SESSION should produce an error.
	 *
	 * @return void
	 */
	public function test_error_session_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 20, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_FILES should produce an error.
	 *
	 * @return void
	 */
	public function test_error_files_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 23, self::WRITE_ERROR );
	}

	/**
	 * Writing to $_ENV should produce an error.
	 *
	 * @return void
	 */
	public function test_error_env_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 26, self::WRITE_ERROR );
	}

	// ---- Compound assignments ----

	/**
	 * Compound assignment += to superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_plus_equal(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 29, self::WRITE_ERROR );
	}

	/**
	 * Compound assignment .= to superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_concat_equal(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 31, self::WRITE_ERROR );
	}

	/**
	 * Compound assignment ??= to superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_coalesce_equal(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 33, self::WRITE_ERROR );
	}

	// ---- Nested key write ----

	/**
	 * Nested key write to superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_nested_key_write(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 36, self::WRITE_ERROR );
	}

	// ---- Unset ----

	/**
	 * Unset on superglobal key should produce an error.
	 *
	 * @return void
	 */
	public function test_error_unset_superglobal_key(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 39, self::UNSET_ERROR );
	}

	/**
	 * Unset with multiple args including superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_unset_multiple_with_superglobal(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 42, self::UNSET_ERROR );
	}

	/**
	 * Unset on entire superglobal should produce an error.
	 *
	 * @return void
	 */
	public function test_error_unset_entire_superglobal(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 45, self::UNSET_ERROR );
	}

	// ---- Whole superglobal assignment ----

	/**
	 * Assigning to the entire superglobal variable should produce an error.
	 *
	 * @return void
	 */
	public function test_error_whole_superglobal_assignment(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 48, self::WRITE_ERROR );
	}

	// ---- Exact counts ----

	/**
	 * Verify exact error count on violations fixture.
	 *
	 * @return void
	 */
	public function test_exact_error_count_on_violations(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'superglobal-write-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 16, $file->getErrorCount() );
	}
}
