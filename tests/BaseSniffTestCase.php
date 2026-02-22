<?php

namespace Emrikol\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for Emrikol sniff tests.
 *
 * Provides helpers to run a single sniff against a fixture file
 * and inspect the resulting errors by line number and error code.
 */
abstract class BaseSniffTestCase extends TestCase {

	/**
	 * Run a specific sniff against a fixture file.
	 *
	 * @param string $fixture_path    Absolute path to the .inc fixture file.
	 * @param string $sniff_code      Dot-separated sniff code (e.g. 'Emrikol.Functions.TypeDeclaration').
	 * @param array  $properties      Optional sniff properties to set (e.g. ['validate_types' => 'true']).
	 * @param array  $config_overrides Optional config overrides (e.g. ['annotations' => false]).
	 *
	 * @return LocalFile The processed PHPCS file object.
	 */
	protected function check_file( string $fixture_path, string $sniff_code, array $properties = array(), array $config_overrides = array() ): LocalFile {
		$config            = new Config();
		$config->standards = array( 'Emrikol' );
		$config->sniffs    = array( $sniff_code );
		$config->ignored   = array();
		$config->cache     = false;

		foreach ( $config_overrides as $key => $value ) {
			$config->$key = $value;
		}

		$ruleset = new Ruleset( $config );

		// Set sniff properties via the ruleset property storage so they survive
		// fixer iterations (populateTokenListeners() recreates sniff objects).
		if ( ! empty( $properties ) ) {
			foreach ( $properties as $key => $value ) {
				if ( is_array( $value ) ) {
					$name      = $key . '[]';
					$formatted = implode( ',', $value );
				} elseif ( is_bool( $value ) ) {
					$name      = $key;
					$formatted = $value ? 'true' : 'false';
				} else {
					$name      = $key;
					$formatted = (string) $value;
				}

				$ruleset->ruleset[ $sniff_code ]['properties'][ $name ] = array(
					'scope' => 'sniff',
					'value' => $formatted,
				);
			}

			// Re-create sniff objects with the new properties applied.
			$ruleset->populateTokenListeners();
		}

		$file = new LocalFile( $fixture_path, $ruleset, $config );
		$file->process();

		return $file;
	}

	/**
	 * Get error counts grouped by line number.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return array<int, int> Line number => total error count on that line.
	 */
	protected function get_error_line_counts( LocalFile $file ): array {
		$errors = $file->getErrors();
		$result = array();

		foreach ( $errors as $line => $columns ) {
			$count = 0;
			foreach ( $columns as $column_errors ) {
				$count += count( $column_errors );
			}
			$result[ $line ] = $count;
		}

		return $result;
	}

	/**
	 * Get all error codes found, grouped by line number.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return array<int, string[]> Line number => list of error codes on that line.
	 */
	protected function get_error_codes_by_line( LocalFile $file ): array {
		$errors = $file->getErrors();
		$result = array();

		foreach ( $errors as $line => $columns ) {
			$codes = array();
			foreach ( $columns as $column_errors ) {
				foreach ( $column_errors as $error ) {
					$codes[] = $error['source'];
				}
			}
			$result[ $line ] = $codes;
		}

		return $result;
	}

	/**
	 * Assert that a specific line has a specific number of errors.
	 *
	 * @param LocalFile $file     The processed file.
	 * @param int       $line     The line number.
	 * @param int       $expected Expected number of errors.
	 *
	 * @return void
	 */
	protected function assert_error_count_on_line( LocalFile $file, int $line, int $expected ): void {
		$line_counts = $this->get_error_line_counts( $file );
		$actual      = $line_counts[ $line ] ?? 0;

		$this->assertSame(
			$expected,
			$actual,
			"Expected {$expected} error(s) on line {$line}, found {$actual}."
		);
	}

	/**
	 * Assert that a specific line has no errors.
	 *
	 * @param LocalFile $file The processed file.
	 * @param int       $line The line number.
	 *
	 * @return void
	 */
	protected function assert_no_error_on_line( LocalFile $file, int $line ): void {
		$this->assert_error_count_on_line( $file, $line, 0 );
	}

	/**
	 * Assert that a file has no errors at all.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return void
	 */
	protected function assert_no_errors( LocalFile $file ): void {
		$this->assertSame(
			0,
			$file->getErrorCount(),
			'Expected no errors, but found ' . $file->getErrorCount() . '.'
		);
	}

	/**
	 * Assert that a specific error code appears on a specific line.
	 *
	 * @param LocalFile $file       The processed file.
	 * @param int       $line       The line number.
	 * @param string    $error_code The full error code (e.g. 'Emrikol.Functions.TypeDeclaration.MissingReturnType').
	 *
	 * @return void
	 */
	protected function assert_error_code_on_line( LocalFile $file, int $line, string $error_code ): void {
		$codes_by_line = $this->get_error_codes_by_line( $file );
		$codes         = $codes_by_line[ $line ] ?? array();

		$this->assertContains(
			$error_code,
			$codes,
			"Expected error code '{$error_code}' on line {$line}. Found: " . implode( ', ', $codes )
		);
	}

	/**
	 * Get warning counts grouped by line number.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return array<int, int> Line number => total warning count on that line.
	 */
	protected function get_warning_line_counts( LocalFile $file ): array {
		$warnings = $file->getWarnings();
		$result   = array();

		foreach ( $warnings as $line => $columns ) {
			$count = 0;
			foreach ( $columns as $column_warnings ) {
				$count += count( $column_warnings );
			}
			$result[ $line ] = $count;
		}

		return $result;
	}

	/**
	 * Get all warning codes found, grouped by line number.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return array<int, string[]> Line number => list of warning codes on that line.
	 */
	protected function get_warning_codes_by_line( LocalFile $file ): array {
		$warnings = $file->getWarnings();
		$result   = array();

		foreach ( $warnings as $line => $columns ) {
			$codes = array();
			foreach ( $columns as $column_warnings ) {
				foreach ( $column_warnings as $warning ) {
					$codes[] = $warning['source'];
				}
			}
			$result[ $line ] = $codes;
		}

		return $result;
	}

	/**
	 * Assert that a specific line has a specific number of warnings.
	 *
	 * @param LocalFile $file     The processed file.
	 * @param int       $line     The line number.
	 * @param int       $expected Expected number of warnings.
	 *
	 * @return void
	 */
	protected function assert_warning_count_on_line( LocalFile $file, int $line, int $expected ): void {
		$line_counts = $this->get_warning_line_counts( $file );
		$actual      = $line_counts[ $line ] ?? 0;

		$this->assertSame(
			$expected,
			$actual,
			"Expected {$expected} warning(s) on line {$line}, found {$actual}."
		);
	}

	/**
	 * Assert that a specific line has no warnings.
	 *
	 * @param LocalFile $file The processed file.
	 * @param int       $line The line number.
	 *
	 * @return void
	 */
	protected function assert_no_warning_on_line( LocalFile $file, int $line ): void {
		$this->assert_warning_count_on_line( $file, $line, 0 );
	}

	/**
	 * Assert that a file has no warnings at all.
	 *
	 * @param LocalFile $file The processed file.
	 *
	 * @return void
	 */
	protected function assert_no_warnings( LocalFile $file ): void {
		$this->assertSame(
			0,
			$file->getWarningCount(),
			'Expected no warnings, but found ' . $file->getWarningCount() . '.'
		);
	}

	/**
	 * Assert that a specific warning code appears on a specific line.
	 *
	 * @param LocalFile $file         The processed file.
	 * @param int       $line         The line number.
	 * @param string    $warning_code The full warning code (e.g. 'Emrikol.PHP.DiscouragedMixedType.MixedParameterType').
	 *
	 * @return void
	 */
	protected function assert_warning_code_on_line( LocalFile $file, int $line, string $warning_code ): void {
		$codes_by_line = $this->get_warning_codes_by_line( $file );
		$codes         = $codes_by_line[ $line ] ?? array();

		$this->assertContains(
			$warning_code,
			$codes,
			"Expected warning code '{$warning_code}' on line {$line}. Found: " . implode( ', ', $codes )
		);
	}

	/**
	 * Get the path to a fixture file relative to the calling test class.
	 *
	 * @param string $filename The fixture filename.
	 *
	 * @return string Absolute path to the fixture.
	 */
	protected function get_fixture_path( string $filename ): string {
		$reflector = new \ReflectionClass( static::class );
		$dir       = dirname( $reflector->getFileName() );

		return $dir . '/fixtures/' . $filename;
	}

	/**
	 * Run phpcbf fix on a file and return the fixed content.
	 *
	 * @param LocalFile $file The processed file (must have been processed already).
	 *
	 * @return string The fixed file content.
	 */
	protected function get_fixed_content( LocalFile $file ): string {
		$file->fixer->fixFile();
		return $file->fixer->getContents();
	}
}
