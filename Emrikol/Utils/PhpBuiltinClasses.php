<?php
/**
 * Emrikol Coding Standards.
 *
 * Runtime detection of PHP built-in classes and interfaces.
 *
 * @package Emrikol\Utils
 */

namespace Emrikol\Utils;

/**
 * Class PhpBuiltinClasses
 *
 * Provides a cached list of all internal (built-in) PHP classes and interfaces
 * available in the current runtime. Uses ReflectionClass to filter out
 * user-defined and anonymous classes.
 */
class PhpBuiltinClasses {

	/**
	 * Cached list of built-in class/interface names.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Returns an array of all internal PHP class and interface names.
	 *
	 * Results are computed once and cached for the lifetime of the process.
	 *
	 * @return array List of built-in class/interface names.
	 */
	public static function get_classes(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		self::$cache = array();

		$all = array_merge( get_declared_classes(), get_declared_interfaces() );

		foreach ( $all as $name ) {
			$reflection = new \ReflectionClass( $name );

			if ( $reflection->isInternal() && ! $reflection->isAnonymous() ) {
				self::$cache[] = $name;
			}
		}

		return self::$cache;
	}
}
