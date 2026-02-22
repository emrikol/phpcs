<?php

// String callback — OK.
add_action( 'init', 'my_init_function' );

// Array callback (object method) — OK.
add_action( 'init', array( $this, 'init_method' ) );

// Short array syntax method reference — OK.
add_filter( 'the_content', [ $this, 'filter_content' ] );

// Variable callback — OK (might be a named function reference).
add_action( 'init', $callback );

// String expression — OK.
add_action( 'init', __NAMESPACE__ . '\\my_func' );

// Static method reference — OK.
add_filter( 'the_title', array( 'MyClass', 'filter_title' ) );

// Not a function call — OK.
$add_action = 'something';

// Closure assigned to variable then passed — OK (variable, not literal closure).
$callback = function() { do_something(); };
add_action( 'init', $callback );

// Named argument with string callback — OK.
add_filter( callback: 'my_filter_func', hook_name: 'the_content' );
