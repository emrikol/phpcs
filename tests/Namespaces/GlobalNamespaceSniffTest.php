<?php
/**
 * Tests for Emrikol\Sniffs\Namespaces\GlobalNamespaceSniff.
 *
 * @package Emrikol\Tests\Namespaces
 */

namespace Emrikol\Tests\Namespaces;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Class GlobalNamespaceSniffTest
 */
class GlobalNamespaceSniffTest extends BaseSniffTestCase {

	/**
	 * Sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.Namespaces.GlobalNamespace';

	/**
	 * Full error code for an unqualified type hint.
	 *
	 * @var string
	 */
	private const ERROR_TYPE_HINT = 'Emrikol.Namespaces.GlobalNamespace.GlobalNamespaceTypeHint';

	/**
	 * Full error code for an unqualified return type.
	 *
	 * @var string
	 */
	private const ERROR_RETURN_TYPE = 'Emrikol.Namespaces.GlobalNamespace.GlobalNamespaceReturnType';

	/**
	 * Full error code for an unqualified string usage (e.g. new expression).
	 *
	 * @var string
	 */
	private const ERROR_STRING = 'Emrikol.Namespaces.GlobalNamespace.GlobalNamespace';

	// -------------------------------------------------------------------------
	// Basic behavior
	// -------------------------------------------------------------------------

	/**
	 * Properly qualified or imported global classes should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_errors_when_properly_qualified(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-correct.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_no_errors( $file );
	}

	/**
	 * Unqualified global classes in namespaced code should produce errors.
	 *
	 * @return void
	 */
	public function test_errors_on_unqualified_classes(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-errors.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass', 'PDO' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		// Line 7: DateTime type hint.
		$this->assert_error_code_on_line( $file, 7, self::ERROR_TYPE_HINT );

		// Line 10: Exception return type.
		$this->assert_error_code_on_line( $file, 10, self::ERROR_RETURN_TYPE );

		// Line 11: Exception in new expression inside function body.
		$this->assert_error_code_on_line( $file, 11, self::ERROR_STRING );

		// Line 14: DateTime in new expression.
		$this->assert_error_code_on_line( $file, 14, self::ERROR_STRING );

		// Line 17: stdClass type hint AND PDO return type — both on the same line.
		$this->assert_error_code_on_line( $file, 17, self::ERROR_TYPE_HINT );
		$this->assert_error_code_on_line( $file, 17, self::ERROR_RETURN_TYPE );

		// Line 18: PDO in new expression inside function body.
		$this->assert_error_code_on_line( $file, 18, self::ERROR_STRING );
	}

	/**
	 * Unqualified class in a type hint should produce a GlobalNamespaceTypeHint error.
	 *
	 * @return void
	 */
	public function test_type_hint_error_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-errors.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass', 'PDO' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_error_code_on_line( $file, 7, self::ERROR_TYPE_HINT );
	}

	/**
	 * Unqualified class used as a return type should produce a GlobalNamespaceReturnType error.
	 *
	 * @return void
	 */
	public function test_return_type_error_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-errors.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass', 'PDO' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_error_code_on_line( $file, 10, self::ERROR_RETURN_TYPE );
	}

	/**
	 * Unqualified class in a new expression should produce a GlobalNamespace error.
	 *
	 * @return void
	 */
	public function test_string_usage_error_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-errors.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass', 'PDO' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_error_code_on_line( $file, 14, self::ERROR_STRING );
	}

	// -------------------------------------------------------------------------
	// Namespace-dependent behavior
	// -------------------------------------------------------------------------

	/**
	 * Files without a namespace declaration should be skipped entirely — no errors.
	 *
	 * @return void
	 */
	public function test_skips_files_without_namespace(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-no-namespace.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_no_errors( $file );
	}

	// -------------------------------------------------------------------------
	// Pattern matching
	// -------------------------------------------------------------------------

	/**
	 * Classes matching configured regex patterns should be flagged.
	 *
	 * @return void
	 */
	public function test_pattern_matching(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-patterns.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array(),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/', '/^Acme_/' ),
			)
		);

		// Line 7: WP_Post type hint.
		$this->assert_error_code_on_line( $file, 7, self::ERROR_TYPE_HINT );

		// Line 10: WP_Query return type.
		$this->assert_error_code_on_line( $file, 10, self::ERROR_RETURN_TYPE );

		// Line 14: Acme_Widget in new expression.
		$this->assert_error_code_on_line( $file, 14, self::ERROR_STRING );
	}

	/**
	 * Classes that do not match any pattern should not be flagged.
	 *
	 * @return void
	 */
	public function test_pattern_no_match_no_error(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-patterns.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array(),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/', '/^Acme_/' ),
			)
		);

		// Line 17: SomeOtherClass — matches no pattern.
		$this->assert_no_error_on_line( $file, 17 );
	}

	// -------------------------------------------------------------------------
	// Auto-detection
	// -------------------------------------------------------------------------

	/**
	 * With auto_detect_php_classes enabled, PHP built-in classes should be flagged.
	 *
	 * @return void
	 */
	public function test_auto_detect_flags_builtins(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-auto-detect.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes' => array(),
				'class_patterns'       => array(),
			)
		);

		// Line 7: DateTime type hint.
		$this->assert_error_code_on_line( $file, 7, self::ERROR_TYPE_HINT );

		// Line 9: Exception return type.
		$this->assert_error_code_on_line( $file, 9, self::ERROR_RETURN_TYPE );

		// Line 10: Exception in new expression inside function body.
		$this->assert_error_code_on_line( $file, 10, self::ERROR_STRING );

		// Line 13: stdClass in new expression.
		$this->assert_error_code_on_line( $file, 13, self::ERROR_STRING );

		// Line 15: PDO return type.
		$this->assert_error_code_on_line( $file, 15, self::ERROR_RETURN_TYPE );

		// Line 16: PDO in new expression inside function body.
		$this->assert_error_code_on_line( $file, 16, self::ERROR_STRING );
	}

	/**
	 * With auto_detect enabled, non-built-in classes should not be flagged.
	 *
	 * @return void
	 */
	public function test_auto_detect_ignores_non_builtins(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-auto-detect.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes' => array(),
				'class_patterns'       => array(),
			)
		);

		// Line 20: WP_Post — not a PHP built-in.
		$this->assert_no_error_on_line( $file, 20 );

		// Line 21: SomeCustomClass — not a PHP built-in.
		$this->assert_no_error_on_line( $file, 21 );
	}

	/**
	 * With auto_detect off and no known classes or patterns, no errors should be raised.
	 *
	 * @return void
	 */
	public function test_auto_detect_off_no_errors(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-auto-detect.inc' ),
			self::SNIFF_CODE,
			array(
				'auto_detect_php_classes' => false,
				'known_global_classes'    => array(),
				'class_patterns'          => array(),
			)
		);

		$this->assert_no_errors( $file );
	}

	// -------------------------------------------------------------------------
	// Use imports
	// -------------------------------------------------------------------------

	/**
	 * Classes imported via use statements should not be flagged, even if they are known globals.
	 *
	 * @return void
	 */
	public function test_imported_classes_not_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-use-imports.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'PDO', 'stdClass' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$this->assert_no_errors( $file );
	}

	// -------------------------------------------------------------------------
	// Mixed scenarios
	// -------------------------------------------------------------------------

	/**
	 * Files with a mix of correct and incorrect usages should only flag the incorrect ones.
	 *
	 * @return void
	 */
	public function test_mixed_correct_and_incorrect(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-mixed.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception', 'stdClass' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		// Lines with imported DateTime — no errors.
		$this->assert_no_error_on_line( $file, 9 );
		$this->assert_no_error_on_line( $file, 10 );

		// Line 14: Exception return type is NOT imported.
		$this->assert_error_code_on_line( $file, 14, self::ERROR_RETURN_TYPE );

		// Line 23: stdClass unqualified, not imported.
		$this->assert_error_code_on_line( $file, 23, self::ERROR_STRING );
	}

	// -------------------------------------------------------------------------
	// Declaration contexts (should NOT be flagged)
	// -------------------------------------------------------------------------

	/**
	 * Namespace, class, interface, trait, and enum declarations should not be flagged
	 * even when the declared name matches a global class pattern.
	 *
	 * @return void
	 */
	public function test_no_errors_on_declarations(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-declarations.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'WP_Widget', 'WP_Interface' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/' ),
			)
		);

		// Namespace declaration — no error.
		$this->assert_no_error_on_line( $file, 4 );

		// Class declaration — no error.
		$this->assert_no_error_on_line( $file, 7 );

		// Interface declaration — no error.
		$this->assert_no_error_on_line( $file, 15 );

		// Trait declaration — no error.
		$this->assert_no_error_on_line( $file, 20 );

		// Enum declaration — no error.
		$this->assert_no_error_on_line( $file, 25 );
	}

	/**
	 * References in extends, implements, type hints, return types, and new expressions
	 * should still be flagged even when declarations are skipped.
	 *
	 * @return void
	 */
	public function test_errors_on_references_in_declaration_file(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-declarations.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'WP_Widget', 'WP_Interface' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/' ),
			)
		);

		// Line 10: new WP_Post() — reference, error.
		$this->assert_error_code_on_line( $file, 10, self::ERROR_STRING );

		// Line 31: extends WP_Widget — reference, error.
		$this->assert_error_code_on_line( $file, 31, self::ERROR_STRING );

		// Line 34: implements WP_Interface — reference, error.
		$this->assert_error_code_on_line( $file, 34, self::ERROR_STRING );

		// Line 37: WP_Post type hint — reference, error.
		$this->assert_error_code_on_line( $file, 37, self::ERROR_TYPE_HINT );

		// Line 40: WP_Query return type — reference, error.
		$this->assert_error_code_on_line( $file, 40, self::ERROR_RETURN_TYPE );

		// Line 42: new WP_Query() — reference, error.
		$this->assert_error_code_on_line( $file, 42, self::ERROR_STRING );
	}

	/**
	 * The fixer should not attempt to prepend backslash to declaration names.
	 *
	 * @return void
	 */
	public function test_fix_does_not_modify_declarations(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-declarations.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'WP_Widget', 'WP_Interface' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/' ),
			)
		);

		$fixed = $this->get_fixed_content( $file );

		// Declarations must NOT have a backslash prepended.
		$this->assertStringContainsString( 'namespace WP_TimeSync;', $fixed, 'Namespace declaration should not be modified.' );
		$this->assertStringContainsString( 'class WP_TimeSync_Plugin', $fixed, 'Class declaration should not be modified.' );
		$this->assertStringContainsString( 'interface WP_TimeSync_Interface', $fixed, 'Interface declaration should not be modified.' );
		$this->assertStringContainsString( 'trait WP_TimeSync_Trait', $fixed, 'Trait declaration should not be modified.' );
		$this->assertStringContainsString( 'enum WP_TimeSync_Status', $fixed, 'Enum declaration should not be modified.' );

		// References SHOULD have a backslash prepended.
		$this->assertStringContainsString( 'extends \WP_Widget', $fixed, 'extends reference should be backslash-prefixed.' );
		$this->assertStringContainsString( 'implements \WP_Interface', $fixed, 'implements reference should be backslash-prefixed.' );
	}

	// -------------------------------------------------------------------------
	// Constant vs class context
	// -------------------------------------------------------------------------

	/**
	 * Constants matching class_patterns should NOT be flagged when used as bare values.
	 *
	 * @return void
	 */
	public function test_constants_not_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-constants.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array(),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/' ),
			)
		);

		// Bare constant usages — no errors.
		$this->assert_no_error_on_line( $file, 10 ); // if ( WP_DEBUG ).
		$this->assert_no_error_on_line( $file, 15 ); // defined('WP_CLI') && WP_CLI.
		$this->assert_no_error_on_line( $file, 20 ); // WP_CONTENT_DIR . '/uploads'.
		$this->assert_no_error_on_line( $file, 23 ); // WP_DEBUG ? 'on' : 'off'.
		$this->assert_no_error_on_line( $file, 27 ); // return WP_DEBUG.
		$this->assert_no_error_on_line( $file, 31 ); // WP_DEBUG === true.
		$this->assert_no_error_on_line( $file, 36 ); // intval( WP_DEBUG ).
	}

	/**
	 * Class usages (new, ::, instanceof, catch) should still be flagged
	 * even for names that also match constant patterns.
	 *
	 * @return void
	 */
	public function test_class_contexts_still_flagged(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-constants.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array(),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array( '/^WP_/' ),
			)
		);

		// Line 43: WP_CLI::add_command() — static access.
		$this->assert_error_code_on_line( $file, 43, self::ERROR_STRING );

		// Line 46: new WP_Error().
		$this->assert_error_code_on_line( $file, 46, self::ERROR_STRING );

		// Line 50: instanceof WP_Query.
		$this->assert_error_code_on_line( $file, 50, self::ERROR_STRING );

		// Line 56: throw new WP_Exception().
		$this->assert_error_code_on_line( $file, 56, self::ERROR_STRING );

		// Line 57: catch ( WP_Exception $e ).
		$this->assert_error_code_on_line( $file, 57, self::ERROR_STRING );
	}

	// -------------------------------------------------------------------------
	// Auto-fix
	// -------------------------------------------------------------------------

	/**
	 * The fixer should prepend a backslash to unqualified global class references.
	 *
	 * @return void
	 */
	public function test_fix_prepends_backslash(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'global-namespace-fix.inc' ),
			self::SNIFF_CODE,
			array(
				'known_global_classes'    => array( 'DateTime', 'Exception' ),
				'auto_detect_php_classes' => false,
				'class_patterns'          => array(),
			)
		);

		$fixed = $this->get_fixed_content( $file );

		$this->assertStringContainsString( '\DateTime', $fixed, 'Expected \\DateTime in fixed output.' );
		$this->assertStringContainsString( '\Exception', $fixed, 'Expected \\Exception in fixed output.' );

		// Verify type hint and return type are backslash-prefixed in context.
		$this->assertMatchesRegularExpression(
			'/function get_date\(\\\\DateTime/',
			$fixed,
			'Type hint DateTime should be backslash-prefixed after fix.'
		);
		$this->assertMatchesRegularExpression(
			'/:\\s*\\\\Exception/',
			$fixed,
			'Return type Exception should be backslash-prefixed after fix.'
		);
	}
}
