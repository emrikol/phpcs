<?php
/**
 * Tests for Emrikol\Sniffs\Namespaces\NamespaceSniff.
 *
 * @package Emrikol\Tests\Namespaces
 */

namespace Emrikol\Tests\Namespaces;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Class NamespaceSniffTest
 */
class NamespaceSniffTest extends BaseSniffTestCase {

	/**
	 * Sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Namespaces.Namespace';

	/**
	 * Full error code for a missing namespace.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.Namespaces.Namespace.MissingNamespace';

	/**
	 * Files with a namespace declaration should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_when_namespace_present(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-present.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Files without a namespace declaration should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_namespace_missing(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount(), 'Expected exactly 1 error.' );
		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * Files with a nested/sub-namespace should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_with_nested_namespace(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-nested.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * A PHP file containing only an opening tag should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_on_empty_file(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-empty-file.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount(), 'Expected exactly 1 error.' );
		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * Files that contain only functions but no namespace should produce 1 error on line 1.
	 *
	 * @return void
	 */
	public function test_error_when_only_functions(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-only-functions.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 1, $file->getErrorCount(), 'Expected exactly 1 error.' );
		$this->assert_error_count_on_line( $file, 1, 1 );
		$this->assert_error_code_on_line( $file, 1, self::ERROR_CODE );
	}

	/**
	 * Files with a namespace and use statements should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_with_use_statements(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-with-use.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * The namespace sniff is not auto-fixable; fixable count must be 0.
	 *
	 * @return void
	 */
	public function test_not_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'namespace-missing.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 0, $file->getFixableCount(), 'Expected 0 fixable errors.' );
	}
}
