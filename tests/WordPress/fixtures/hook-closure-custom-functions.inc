<?php

// Custom hook wrapper — should be flagged when configured.
my_add_action( 'init', function() {
	do_something();
} );

// Standard add_action — should still be flagged.
add_action( 'init', function() {
	do_something();
} );

// Custom wrapper with arrow function — should be flagged when configured.
my_add_filter( 'hook', fn() => true );

// Standard functions not in the custom list — should NOT be flagged.
add_filter( 'hook', function() { return true; } );
