<?php
/**
 * Test bootstrap file.
 *
 * Loads the Composer autoloader (for PHPUnit and PHPCS classes)
 * and registers a simple autoloader for Emrikol test and utility classes.
 */

// PHPCS constants required before loading the autoloader.
if ( ! defined( 'PHP_CODESNIFFER_VERBOSITY' ) ) {
	define( 'PHP_CODESNIFFER_VERBOSITY', 0 );
}
if ( ! defined( 'PHP_CODESNIFFER_CBF' ) ) {
	define( 'PHP_CODESNIFFER_CBF', false );
}
if ( ! defined( 'PHP_CODESNIFFER_IN_TESTS' ) ) {
	define( 'PHP_CODESNIFFER_IN_TESTS', true );
}

// Composer autoloader (PHPUnit, etc.)
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// PHPCS autoloader (Config, Ruleset, LocalFile, etc.)
require_once dirname( __DIR__ ) . '/vendor/squizlabs/php_codesniffer/autoload.php';

// Initialize PHPCS custom token constants (T_OPEN_CURLY_BRACKET, etc.)
new \PHP_CodeSniffer\Util\Tokens();

// Autoloader for Emrikol\Tests\ namespace.
spl_autoload_register( function ( $class ) {
	$prefix = 'Emrikol\\Tests\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Autoloader for Emrikol\Utils\ namespace.
spl_autoload_register( function ( $class ) {
	$prefix = 'Emrikol\\Utils\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = dirname( __DIR__ ) . '/Emrikol/Utils/' . str_replace( '\\', '/', $relative ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );
