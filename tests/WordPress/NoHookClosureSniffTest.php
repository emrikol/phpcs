<?php

namespace Emrikol\Tests\WordPress;

use Emrikol\Tests\BaseSniffTestCase;

/**
 * Tests for Emrikol.WordPress.NoHookClosure sniff.
 *
 * @package Emrikol\Tests\WordPress
 */
class NoHookClosureSniffTest extends BaseSniffTestCase {

	/**
	 * The sniff code under test.
	 *
	 * @var string
	 */
	private const SNIFF_CODE = 'Emrikol.WordPress.NoHookClosure';

	/**
	 * The full error code for closure/arrow function callbacks.
	 *
	 * @var string
	 */
	private const ERROR_CODE = 'Emrikol.WordPress.NoHookClosure.ClosureHookCallback';

	/**
	 * The full error code for Closure::fromCallable/bind wrapper callbacks.
	 *
	 * @var string
	 */
	private const WRAPPER_ERROR_CODE = 'Emrikol.WordPress.NoHookClosure.ClosureWrapperCallback';

	/**
	 * The full error code for first-class callable callbacks.
	 *
	 * @var string
	 */
	private const FIRST_CLASS_ERROR_CODE = 'Emrikol.WordPress.NoHookClosure.FirstClassCallableCallback';

	/**
	 * The full warning code for variable callbacks.
	 *
	 * @var string
	 */
	private const VARIABLE_WARNING_CODE = 'Emrikol.WordPress.NoHookClosure.VariableCallback';

	/**
	 * The full warning code for indirect hook registration.
	 *
	 * @var string
	 */
	private const INDIRECT_WARNING_CODE = 'Emrikol.WordPress.NoHookClosure.IndirectHookRegistration';

	// ---- Correct usage (no errors) ----

	/**
	 * Named function, array callback, variable, string expression, and
	 * variable-assigned closure should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_with_proper_callbacks(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-correct.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_errors( $file );
	}

	// ---- Violations ----

	/**
	 * Closure in add_action should produce an error.
	 *
	 * @return void
	 */
	public function test_error_on_closure_in_add_action(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );
	}

	/**
	 * Arrow function in add_filter should produce an error.
	 *
	 * @return void
	 */
	public function test_error_on_arrow_function_in_add_filter(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );
	}

	/**
	 * Closure in add_filter should produce an error.
	 *
	 * @return void
	 */
	public function test_error_on_closure_in_add_filter(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 12, 1 );
		$this->assert_error_code_on_line( $file, 12, self::ERROR_CODE );
	}

	/**
	 * Exact error count on violations fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_violations_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-violations.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 3, $file->getErrorCount(), 'Violations fixture should have exactly 3 errors.' );
	}

	// ---- Edge cases: should flag ----

	/**
	 * Nested parentheses in first argument should still detect closure.
	 *
	 * @return void
	 */
	public function test_nested_parens_in_first_arg(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );
	}

	/**
	 * Namespaced calls (\add_action) should still be detected.
	 *
	 * @return void
	 */
	public function test_namespaced_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );
	}

	/**
	 * Named argument with closure value should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_named_arg_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 35, 1 );
		$this->assert_error_code_on_line( $file, 35, self::ERROR_CODE );
	}

	/**
	 * Reordered named arguments with closure should be detected.
	 *
	 * @return void
	 */
	public function test_error_on_reordered_named_arg_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 38, 1 );
		$this->assert_error_code_on_line( $file, 38, self::ERROR_CODE );
	}

	/**
	 * Closure body with commas inside should still flag the closure.
	 *
	 * @return void
	 */
	public function test_error_on_closure_body_with_commas(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 56, 1 );
		$this->assert_error_code_on_line( $file, 56, self::ERROR_CODE );
	}

	/**
	 * Multiline hook call should still detect the closure.
	 *
	 * @return void
	 */
	public function test_error_on_multiline_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 61, 1 );
		$this->assert_error_code_on_line( $file, 61, self::ERROR_CODE );
	}

	// ---- Edge cases: should NOT flag ----

	/**
	 * Calls with too few arguments should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_on_single_argument(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 14 );
	}

	/**
	 * Third argument being a closure should not flag (only 2nd positional arg matters).
	 *
	 * @return void
	 */
	public function test_no_error_on_third_arg_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 17 );
	}

	/**
	 * Variable callbacks should produce no errors.
	 *
	 * @return void
	 */
	public function test_no_error_on_variable_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 20 );
	}

	/**
	 * Object method call ($obj->add_action) should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_object_method_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 23 );
	}

	/**
	 * Static method call (MyClass::add_action) should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_static_method_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 26 );
	}

	/**
	 * Static filter call (SomeClass::add_filter) should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_static_filter_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 29 );
	}

	/**
	 * Nullsafe operator ($obj?->add_action) should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_nullsafe_method_call(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 32 );
	}

	/**
	 * Named argument with string callback should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_named_arg_string_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 41 );
	}

	/**
	 * Empty argument list should not crash or error.
	 *
	 * @return void
	 */
	public function test_no_error_on_empty_args(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 44 );
	}

	/**
	 * Closure as first positional argument should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_closure_as_first_positional_arg(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 47 );
	}

	/**
	 * apply_filters is NOT in default hook_functions — should NOT flag.
	 *
	 * @return void
	 */
	public function test_no_error_on_apply_filters(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 50 );
	}

	/**
	 * do_action is NOT in default hook_functions — should NOT flag.
	 *
	 * @return void
	 */
	public function test_no_error_on_do_action(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 53 );
	}

	/**
	 * Static closure should be flagged (still a closure, can't be unhooked).
	 *
	 * @return void
	 */
	public function test_error_on_static_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 67, 1 );
		$this->assert_error_code_on_line( $file, 67, self::ERROR_CODE );
	}

	/**
	 * Static arrow function should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_static_arrow_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 70, 1 );
		$this->assert_error_code_on_line( $file, 70, self::ERROR_CODE );
	}

	/**
	 * Named argument with static arrow function should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_named_arg_static_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 73, 1 );
		$this->assert_error_code_on_line( $file, 73, self::ERROR_CODE );
	}

	/**
	 * Exact error count on edge-cases fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_edge_cases_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 21, $file->getErrorCount(), 'Edge-cases fixture should have exactly 21 errors.' );
	}

	// ---- Custom hook_functions property ----

	/**
	 * Custom hook_functions property should detect custom wrapper functions.
	 *
	 * @return void
	 */
	public function test_custom_hook_functions_detect_wrappers(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-custom-functions.inc' ),
			self::SNIFF_CODE,
			array( 'hook_functions' => 'my_add_action,my_add_filter,add_action' )
		);

		// my_add_action with closure — error.
		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );

		// Standard add_action still in list — error.
		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::ERROR_CODE );

		// my_add_filter with arrow function — error.
		$this->assert_error_count_on_line( $file, 14, 1 );
		$this->assert_error_code_on_line( $file, 14, self::ERROR_CODE );
	}

	/**
	 * Standard function removed from custom list should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_custom_hook_functions_excludes_unlisted(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-custom-functions.inc' ),
			self::SNIFF_CODE,
			array( 'hook_functions' => 'my_add_action,my_add_filter,add_action' )
		);

		// add_filter NOT in list — no error.
		$this->assert_no_error_on_line( $file, 17 );
	}

	/**
	 * Custom hook_functions with spaces around commas should still work.
	 *
	 * @return void
	 */
	public function test_custom_hook_functions_with_spaces(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-custom-functions.inc' ),
			self::SNIFF_CODE,
			array( 'hook_functions' => 'my_add_action , my_add_filter , add_action' )
		);

		// All three custom functions should still be detected despite spaces.
		$this->assertSame( 3, $file->getErrorCount(), 'Spaces in hook_functions should be trimmed correctly.' );
	}

	/**
	 * Exact error count on custom-functions fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_custom_functions_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-custom-functions.inc' ),
			self::SNIFF_CODE,
			array( 'hook_functions' => 'my_add_action,my_add_filter,add_action' )
		);

		$this->assertSame( 3, $file->getErrorCount(), 'Custom-functions fixture should have exactly 3 errors.' );
	}

	// ---- Real-world patterns ----

	/**
	 * Comment between comma and closure should still flag.
	 *
	 * @return void
	 */
	public function test_error_on_comment_between_comma_and_closure(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 4, 1 );
		$this->assert_error_code_on_line( $file, 4, self::ERROR_CODE );
	}

	/**
	 * First-class callable syntax (PHP 8.1) creates a Closure — should flag.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 9, 1 );
		$this->assert_error_code_on_line( $file, 9, self::FIRST_CLASS_ERROR_CODE );
	}

	/**
	 * Spread operator as second arg should NOT flag.
	 *
	 * @return void
	 */
	public function test_no_error_on_spread_operator(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 12 );
	}

	/**
	 * Ternary with closure branch is NOT flagged (accepted false negative).
	 *
	 * @return void
	 */
	public function test_no_error_on_ternary_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 16 );
	}

	/**
	 * Nested hook calls — both closures should flag.
	 *
	 * @return void
	 */
	public function test_error_on_nested_hook_closures(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 19, 1 );
		$this->assert_error_count_on_line( $file, 20, 1 );
	}

	/**
	 * Hook inside conditional should still flag.
	 *
	 * @return void
	 */
	public function test_error_on_hook_in_conditional(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 27, 1 );
	}

	/**
	 * Hook inside foreach should still flag.
	 *
	 * @return void
	 */
	public function test_error_on_hook_in_foreach(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 34, 1 );
	}

	/**
	 * Arrow function with complex expression should flag.
	 *
	 * @return void
	 */
	public function test_error_on_complex_arrow_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 38, 1 );
	}

	/**
	 * Comment between function name and open paren should still flag.
	 *
	 * @return void
	 */
	public function test_error_on_comment_between_function_name_and_paren(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 41, 1 );
	}

	/**
	 * Inline comment in arg list should still find the closure.
	 *
	 * @return void
	 */
	public function test_error_on_inline_comment_in_arg_list(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 46, 1 );
	}

	/**
	 * Exact error count on real-world fixture to catch false positives.
	 *
	 * @return void
	 */
	public function test_exact_count_on_real_world_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-real-world.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 9, $file->getErrorCount(), 'Real-world fixture should have exactly 9 errors.' );
	}

	// ---- Closure::fromCallable / Closure::bind ----

	/**
	 * Closure::fromCallable() as callback should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_closure_from_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 76, 1 );
		$this->assert_error_code_on_line( $file, 76, self::WRAPPER_ERROR_CODE );
	}

	/**
	 * Closure::bind() as callback should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_closure_bind(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 79, 1 );
		$this->assert_error_code_on_line( $file, 79, self::WRAPPER_ERROR_CODE );
	}

	/**
	 * Namespaced \Closure::fromCallable() should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_namespaced_closure_from_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 82, 1 );
		$this->assert_error_code_on_line( $file, 82, self::WRAPPER_ERROR_CODE );
	}

	/**
	 * Named arg with Closure::fromCallable should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_named_arg_closure_from_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 85, 1 );
		$this->assert_error_code_on_line( $file, 85, self::WRAPPER_ERROR_CODE );
	}

	/**
	 * Closure::otherMethod() should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_closure_other_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 88 );
	}

	/**
	 * Variable::fromCallable() should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_variable_from_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 91 );
	}

	/**
	 * SomeClass::fromCallable() should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_other_class_from_callable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 94 );
	}

	// ---- Anonymous class callbacks ----

	/**
	 * Anonymous class with __invoke should be flagged.
	 *
	 * @var string
	 */
	private const ANON_CLASS_ERROR_CODE = 'Emrikol.WordPress.NoHookClosure.AnonymousClassCallback';

	/**
	 * Anonymous class as add_action callback should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_anonymous_class_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 97, 1 );
		$this->assert_error_code_on_line( $file, 97, self::ANON_CLASS_ERROR_CODE );
	}

	/**
	 * Anonymous class with constructor args should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_anonymous_class_with_constructor_args(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 104, 1 );
		$this->assert_error_code_on_line( $file, 104, self::ANON_CLASS_ERROR_CODE );
	}

	/**
	 * Named class instantiation should NOT be flagged.
	 *
	 * @return void
	 */
	public function test_no_error_on_named_class_instantiation(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 111 );
	}

	/**
	 * Anonymous class as named arg should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_named_arg_anonymous_class(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 114, 1 );
		$this->assert_error_code_on_line( $file, 114, self::ANON_CLASS_ERROR_CODE );
	}

	// ---- First-class callable syntax ----

	/**
	 * First-class callable from named function should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable_named_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 121, 1 );
		$this->assert_error_code_on_line( $file, 121, self::FIRST_CLASS_ERROR_CODE );
	}

	/**
	 * Namespaced first-class callable should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable_namespaced(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 124, 1 );
		$this->assert_error_code_on_line( $file, 124, self::FIRST_CLASS_ERROR_CODE );
	}

	/**
	 * Static method first-class callable should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable_static_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 127, 1 );
		$this->assert_error_code_on_line( $file, 127, self::FIRST_CLASS_ERROR_CODE );
	}

	/**
	 * Instance method first-class callable should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable_instance_method(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 130, 1 );
		$this->assert_error_code_on_line( $file, 130, self::FIRST_CLASS_ERROR_CODE );
	}

	/**
	 * Named arg first-class callable should be flagged.
	 *
	 * @return void
	 */
	public function test_error_on_first_class_callable_named_arg(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 133, 1 );
		$this->assert_error_code_on_line( $file, 133, self::FIRST_CLASS_ERROR_CODE );
	}

	// ---- Variable callbacks (warnings) ----

	/**
	 * Variable callback should produce a warning on existing line 20.
	 *
	 * @return void
	 */
	public function test_warning_on_existing_variable_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 20, 1 );
		$this->assert_warning_code_on_line( $file, 20, self::VARIABLE_WARNING_CODE );
	}

	/**
	 * Variable callback should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_variable_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 138, 1 );
		$this->assert_warning_code_on_line( $file, 138, self::VARIABLE_WARNING_CODE );
	}

	/**
	 * Named arg variable callback should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_named_arg_variable_callback(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 141, 1 );
		$this->assert_warning_code_on_line( $file, 141, self::VARIABLE_WARNING_CODE );
	}

	// ---- Indirect hook registration (warnings) ----

	/**
	 * call_user_func with add_action should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_indirect_call_user_func(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 146, 1 );
		$this->assert_warning_code_on_line( $file, 146, self::INDIRECT_WARNING_CODE );
	}

	/**
	 * call_user_func_array with add_action should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_indirect_call_user_func_array(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 149, 1 );
		$this->assert_warning_code_on_line( $file, 149, self::INDIRECT_WARNING_CODE );
	}

	/**
	 * call_user_func with non-hook function should NOT warn.
	 *
	 * @return void
	 */
	public function test_no_warning_on_non_hook_call_user_func(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_warning_on_line( $file, 152 );
	}

	/**
	 * call_user_func with add_filter should produce a warning.
	 *
	 * @return void
	 */
	public function test_warning_on_indirect_call_user_func_filter(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-edge-cases.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_warning_count_on_line( $file, 155, 1 );
		$this->assert_warning_code_on_line( $file, 155, self::INDIRECT_WARNING_CODE );
	}

	// ---- Not auto-fixable ----

	/**
	 * The sniff should not produce fixable errors.
	 *
	 * @return void
	 */
	public function test_not_auto_fixable(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-violations.inc' ),
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
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
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
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 8, 1 );
		$this->assert_error_code_on_line( $file, 8, self::ERROR_CODE );
	}

	/**
	 * Inline phpcs:ignore with note separator should still suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_with_note(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 10 );
	}

	/**
	 * Inline phpcs:ignore with parent sniff code should suppress.
	 *
	 * @return void
	 */
	public function test_inline_suppression_parent_code(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 13 );
	}

	/**
	 * Unsuppressed closure hook should still error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_no_suppress(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_error_count_on_line( $file, 17, 1 );
	}

	/**
	 * Arrow function with correct suppression should not error.
	 *
	 * @return void
	 */
	public function test_inline_suppression_arrow_function(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assert_no_error_on_line( $file, 19 );
	}

	/**
	 * Exact error count on inline suppression fixture.
	 *
	 * @return void
	 */
	public function test_exact_count_on_inline_suppression_fixture(): void {
		$file = $this->check_file(
			$this->get_fixture_path( 'hook-closure-inline-suppression.inc' ),
			self::SNIFF_CODE
		);

		$this->assertSame( 2, $file->getErrorCount(), 'Inline suppression fixture should have exactly 2 errors.' );
	}
}
