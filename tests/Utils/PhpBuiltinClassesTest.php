<?php
/**
 * Emrikol Coding Standards.
 *
 * Unit tests for the PhpBuiltinClasses utility class.
 *
 * @package Emrikol\Tests\Utils
 */

namespace Emrikol\Tests\Utils;

use Emrikol\Utils\PhpBuiltinClasses;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/Emrikol/Utils/PhpBuiltinClasses.php';

/**
 * Class PhpBuiltinClassesTest
 *
 * Tests for PhpBuiltinClasses::get_classes().
 */
class PhpBuiltinClassesTest extends TestCase {

	/**
	 * The result of get_classes(), fetched once for the suite.
	 *
	 * @var array
	 */
	private array $classes;

	/**
	 * Fetch the class list before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->classes = PhpBuiltinClasses::get_classes();
	}

	/**
	 * The returned list must not be empty.
	 *
	 * @return void
	 */
	public function test_returns_non_empty_array(): void {
		$this->assertNotEmpty( $this->classes, 'get_classes() should return a non-empty array.' );
	}

	/**
	 * Well-known built-in classes must be present.
	 *
	 * @dataProvider provider_builtin_classes
	 *
	 * @param string $class_name A PHP built-in class name.
	 *
	 * @return void
	 */
	public function test_contains_builtin_classes( string $class_name ): void {
		$this->assertContains(
			$class_name,
			$this->classes,
			"Expected built-in class '{$class_name}' to appear in get_classes()."
		);
	}

	/**
	 * Data provider: well-known PHP built-in classes.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provider_builtin_classes(): array {
		return array(
			'DateTime'                => array( 'DateTime' ),
			'Exception'               => array( 'Exception' ),
			'stdClass'                => array( 'stdClass' ),
			'PDO'                     => array( 'PDO' ),
			'RuntimeException'        => array( 'RuntimeException' ),
			'InvalidArgumentException' => array( 'InvalidArgumentException' ),
		);
	}

	/**
	 * Well-known built-in interfaces must be present.
	 *
	 * @dataProvider provider_builtin_interfaces
	 *
	 * @param string $interface_name A PHP built-in interface name.
	 *
	 * @return void
	 */
	public function test_contains_builtin_interfaces( string $interface_name ): void {
		$this->assertContains(
			$interface_name,
			$this->classes,
			"Expected built-in interface '{$interface_name}' to appear in get_classes()."
		);
	}

	/**
	 * Data provider: well-known PHP built-in interfaces.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provider_builtin_interfaces(): array {
		return array(
			'Iterator'         => array( 'Iterator' ),
			'Countable'        => array( 'Countable' ),
			'Serializable'     => array( 'Serializable' ),
			'Throwable'        => array( 'Throwable' ),
			'Traversable'      => array( 'Traversable' ),
			'ArrayAccess'      => array( 'ArrayAccess' ),
			'IteratorAggregate' => array( 'IteratorAggregate' ),
			'Stringable'       => array( 'Stringable' ),
		);
	}

	/**
	 * User-defined classes (including this test class) must NOT appear in the list.
	 *
	 * @return void
	 */
	public function test_does_not_contain_user_defined_classes(): void {
		$this->assertNotContains(
			PhpBuiltinClassesTest::class,
			$this->classes,
			'User-defined class ' . PhpBuiltinClassesTest::class . ' should not appear in get_classes().'
		);

		$this->assertNotContains(
			PhpBuiltinClasses::class,
			$this->classes,
			'User-defined class ' . PhpBuiltinClasses::class . ' should not appear in get_classes().'
		);
	}

	/**
	 * Every entry in the returned array must be a string.
	 *
	 * @return void
	 */
	public function test_all_entries_are_strings(): void {
		foreach ( $this->classes as $index => $entry ) {
			$this->assertIsString(
				$entry,
				"Entry at index {$index} is not a string."
			);
		}
	}

	/**
	 * The returned list must contain no duplicate names.
	 *
	 * @return void
	 */
	public function test_no_duplicates(): void {
		$unique = array_unique( $this->classes );

		$this->assertSame(
			count( $unique ),
			count( $this->classes ),
			'get_classes() returned duplicate entries: ' . implode( ', ', array_diff_assoc( $this->classes, $unique ) )
		);
	}

	/**
	 * A second call to get_classes() must return the identical array (caching).
	 *
	 * @return void
	 */
	public function test_second_call_returns_identical_result(): void {
		$first  = PhpBuiltinClasses::get_classes();
		$second = PhpBuiltinClasses::get_classes();

		$this->assertSame(
			$first,
			$second,
			'get_classes() should return the same array instance on repeated calls.'
		);
	}

	/**
	 * Every class in the returned list must be flagged as internal by ReflectionClass.
	 *
	 * @return void
	 */
	public function test_every_entry_is_actually_internal(): void {
		foreach ( $this->classes as $name ) {
			$reflection = new \ReflectionClass( $name );

			$this->assertTrue(
				$reflection->isInternal(),
				"'{$name}' is in get_classes() but ReflectionClass reports it is not internal."
			);
		}
	}
}
