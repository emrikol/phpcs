<?php

// Closure callback — ERROR.
add_action( 'init', function() {
	do_something();
} );

// Arrow function callback — ERROR.
add_filter( 'the_content', fn( $content ) => $content . ' suffix' );

// Closure in add_filter — ERROR.
add_filter( 'the_title', function( $title ) {
	return strtoupper( $title );
} );
