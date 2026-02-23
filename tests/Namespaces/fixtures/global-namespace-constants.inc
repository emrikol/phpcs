<?php
declare(strict_types=1);

namespace MyApp;

// Constants matching class_patterns should NOT be flagged.
// These are bare constant usages, not class references.

// Line 10: WP_DEBUG as boolean constant — no error.
if ( WP_DEBUG ) {
	echo 'debug mode';
}

// Line 15: WP_CLI as boolean constant — no error.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	echo 'cli mode';
}

// Line 20: WP_CONTENT_DIR as path constant — no error.
$path = WP_CONTENT_DIR . '/uploads';

// Line 23: Constant in ternary — no error.
$debug = WP_DEBUG ? 'on' : 'off';

// Line 26: Constant in return — no error.
function is_debug(): bool {
	return WP_DEBUG;
}

// Line 31: Constant in comparison — no error.
if ( WP_DEBUG === true ) {
	echo 'yes';
}

// Line 36: Constant as function argument — no error.
$val = intval( WP_DEBUG );

// ---------------------------------------------------------------
// Class usages that SHOULD still be flagged.
// ---------------------------------------------------------------

// Line 43: WP_CLI static method call — error (GlobalNamespace).
WP_CLI::add_command( 'test', 'test_cmd' );

// Line 46: new WP_Error — error (GlobalNamespace).
$error = new WP_Error( 'code', 'message' );

// Line 50: instanceof WP_Query — error (GlobalNamespace).
function check( $query ): bool {
	return $query instanceof WP_Query;
}

// Lines 56-57: catch WP_Exception — errors (GlobalNamespace).
function risky(): void {
	try {
		throw new WP_Exception();
	} catch ( WP_Exception $e ) {
		echo 'caught';
	}
}
