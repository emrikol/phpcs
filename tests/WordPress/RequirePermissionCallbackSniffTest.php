<?php

namespace Emrikol\Tests\WordPress;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.WordPress.RequirePermissionCallback sniff.
 *
 * @package Emrikol\Tests\WordPress
 */
class RequirePermissionCallbackSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.WordPress.RequirePermissionCallback';

	/**
	 * The full error code for missing permission callback.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.WordPress.RequirePermissionCallback.MissingPermissionCallback';

	// ---- Correct usage (no errors) ----

	/**
	 * Correct register_rest_route calls with permission_callback should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_on_correct_usage(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Violations ----

	/**
	 * Missing permission_callback in long array syntax should produce an error.
	 *
	 * @return void
	 */
	public function test_error_missing_permission_callback_long_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 5, self::ERROR_CODE );
	}

	/**
	 * Missing permission_callback in short array syntax should produce an error.
	 *
	 * @return void
	 */
	public function test_error_missing_permission_callback_short_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 11, self::ERROR_CODE );
	}

	/**
	 * Multi-route with only second sub-array missing permission_callback.
	 *
	 * @return void
	 */
	public function test_error_multi_route_second_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		// First sub-array has permission_callback — no error on line 18.
		$this->assert_no_error_on_line( $file, 18 );
		// Second sub-array missing — error on line 23.
		$this->assert_error_code_on_line( $file, 23, self::ERROR_CODE );
	}

	/**
	 * Multi-route with both sub-arrays missing permission_callback.
	 *
	 * @return void
	 */
	public function test_error_multi_route_both_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 31, self::ERROR_CODE );
		$this->assert_error_code_on_line( $file, 35, self::ERROR_CODE );
	}

	/**
	 * Empty args array should produce an error.
	 *
	 * @return void
	 */
	public function test_error_empty_args_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 42, self::ERROR_CODE );
	}

	/**
	 * Empty short array args should produce an error.
	 *
	 * @return void
	 */
	public function test_error_empty_short_array_args(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 45, self::ERROR_CODE );
	}

	/**
	 * Multi-route with short array — missing permission_callback.
	 *
	 * @return void
	 */
	public function test_error_multi_route_short_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 49, self::ERROR_CODE );
	}

	/**
	 * Namespaced \register_rest_route() call should still be flagged.
	 *
	 * @return void
	 */
	public function test_error_namespaced_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_code_on_line( $file, 56, self::ERROR_CODE );
	}

	/**
	 * Verify exact error count on violations fixture.
	 *
	 * @return void
	 */
	public function test_exact_error_count_on_violations(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 9, $file->getErrorCount() );
	}

	// ---- Correct usage: variable args, method calls, etc. ----

	/**
	 * Variable args should not be flagged (can't check statically).
	 *
	 * @return void
	 */
	public function test_no_error_variable_args(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-correct.inc' ),
			self::SNIFF_CODE
		);

		// Line 45: register_rest_route( 'ns', '/items', $args )
		$this->assert_no_error_on_line( $file, 45 );
	}

	/**
	 * Method calls should not be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_method_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'permission-callback-correct.inc' ),
			self::SNIFF_CODE
		);

		// Line 58: $obj->register_rest_route(...)
		$this->assert_no_error_on_line( $file, 58 );
	}
}
