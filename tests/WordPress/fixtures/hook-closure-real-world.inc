<?php

// Comment between comma and closure — should still flag.
add_action( 'init', /* priority callback */ function() {
	do_something();
} );

// First-class callable syntax (PHP 8.1) — creates a Closure, should flag.
add_action( 'init', my_init_function(...) );

// Spread operator as second arg — should NOT flag.
add_action( 'init', ...$callbacks );

// Ternary: closure branch — sniff sees T_VARIABLE, not T_CLOSURE.
// Documenting current behavior: NOT flagged (false negative, accepted).
add_action( 'init', $debug ? function() {} : 'prod_handler' );

// Nested hook calls — both closures should flag.
add_action( 'init', function() {
	add_filter( 'the_content', function( $content ) {
		return $content;
	} );
} );

// Hook call inside conditional — should still flag.
if ( is_admin() ) {
	add_action( 'admin_init', function() {
		do_admin_thing();
	} );
}

// Hook call inside foreach — should still flag.
foreach ( $hooks as $hook ) {
	add_filter( $hook, fn() => true );
}

// Arrow function with complex expression — should flag.
add_filter( 'the_title', fn( $title ) => strtoupper( trim( $title ) ) . ' - ' . get_bloginfo( 'name' ) );

// Comment between function name and open paren — unusual but valid PHP.
add_action /* weird comment */ ( 'init', function() {} );

// Inline comment in arg list — should still find the closure.
add_filter(
	'the_content', // first arg
	function( $content ) { // closure callback
		return $content;
	}
);
