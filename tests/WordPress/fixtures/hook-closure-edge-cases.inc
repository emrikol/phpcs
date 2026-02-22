<?php

// Nested parentheses in first argument — should still find the closure.
add_action( some_func( 'arg' ), function() {
	do_something();
} );

// Namespaced call — should still be detected.
\add_action( 'init', function() {
	do_something();
} );

// Not enough arguments — no error.
add_action( 'init' );

// Third argument is closure, but second is string — no error on second arg.
add_action( 'init', 'my_func', function() {} );

// Variable as callback — no error.
add_filter( 'the_content', $some_callback );

// Object method call — NOT a global function call, should NOT flag.
$obj->add_action( 'init', function() {} );

// Static method call — NOT a global function call, should NOT flag.
MyClass::add_action( 'init', function() {} );

// Double-colon token before function name should not match.
SomeClass::add_filter( 'hook', fn() => true );

// Nullsafe operator — NOT a global function call, should NOT flag.
$obj?->add_action( 'init', function() {} );

// Named argument: positional first arg + named callback — should flag.
add_action( 'init', callback: function() {} );

// Named argument: reordered, callback first — should flag.
add_action( callback: fn() => true, hook_name: 'init' );

// Named argument: string callback — should NOT flag.
add_action( hook_name: 'init', callback: 'my_function' );

// Empty argument list — no crash, no error.
add_action();

// Closure as FIRST positional argument — should NOT flag (not callback position).
add_action( function() {}, 'not_a_real_call' );

// apply_filters is NOT in default hook_functions — should NOT flag.
apply_filters( 'hook', function() { return true; } );

// do_action is NOT in default hook_functions — should NOT flag.
do_action( 'hook', function() {} );

// Closure body with commas inside (depth tracking verification) — should flag closure.
add_action( some_func( 1, 2, 3 ), function() { some_call( $a, $b ); } );

// Multiline call — should flag.
add_filter(
	'the_content',
	function( $content ) {
		return $content;
	}
);

// Static closure — should flag (still a closure, can't be unhooked).
add_action( 'init', static function() { do_stuff(); } );

// Static arrow function — should flag.
add_filter( 'the_content', static fn( $content ) => $content );

// Named arg with static closure — should flag.
add_action( hook_name: 'init', callback: static fn() => true );

// Closure::fromCallable() — creates unhookable closure wrapper. Should flag.
add_action( 'init', Closure::fromCallable( 'my_function' ) );

// Closure::bind() — creates unhookable bound closure. Should flag.
add_filter( 'the_content', Closure::bind( $some_closure, $this ) );

// \Closure::fromCallable() — namespaced, should still flag.
add_action( 'init', \Closure::fromCallable( [ $this, 'method' ] ) );

// Closure::fromCallable as named arg — should flag.
add_action( hook_name: 'init', callback: Closure::fromCallable( 'my_func' ) );

// Closure::otherMethod() — NOT fromCallable/bind, should NOT flag.
add_action( 'init', Closure::otherMethod( 'something' ) );

// $closure::fromCallable() — variable, not Closure class, should NOT flag.
add_action( 'init', $closure::fromCallable( 'something' ) );

// SomeClass::fromCallable() — not Closure class, should NOT flag.
add_action( 'init', SomeClass::fromCallable( 'something' ) );

// Anonymous class with __invoke — should flag.
add_action( 'init', new class {
	public function __invoke() {
		do_something();
	}
} );

// Anonymous class with constructor args — should flag.
add_filter( 'the_content', new class( $some_dep ) {
	public function __invoke( $content ) {
		return $content;
	}
} );

// Named class instantiation — should NOT flag (named, can be referenced).
add_action( 'init', new SomeNamedClass() );

// Anonymous class as named arg — should flag.
add_action( hook_name: 'init', callback: new class {
	public function __invoke() {}
} );

// --- First-class callable syntax (creates Closure objects, can't be unhooked) ---

// First-class callable from named function — should error.
add_action( 'init', my_function(...) );

// Namespaced first-class callable — should error.
add_action( 'init', \MyNamespace\my_function(...) );

// Static method first-class callable — should error.
add_filter( 'the_content', MyClass::my_method(...) );

// Instance method first-class callable — should error.
add_filter( 'the_content', $obj->my_method(...) );

// Named arg first-class callable — should error.
add_action( hook_name: 'init', callback: my_function(...) );

// --- Variable callbacks (warning: manual inspection needed) ---

// Variable callback — should warn.
add_action( 'init', $some_var );

// Named arg variable callback — should warn.
add_action( hook_name: 'init', callback: $some_var );

// --- Indirect hook registration (warning: manual inspection needed) ---

// call_user_func with add_action — should warn.
call_user_func( 'add_action', 'init', function() { do_something(); } );

// call_user_func_array with add_action — should warn.
call_user_func_array( 'add_action', [ 'init', function() {} ] );

// call_user_func with non-hook function — should NOT warn.
call_user_func( 'some_function', 'arg1', 'arg2' );

// call_user_func with add_filter — should warn.
call_user_func( 'add_filter', 'the_content', 'my_filter' );
