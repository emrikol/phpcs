<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

// 1. Correct inline suppression — no error expected (line 4).
add_action( 'init', function() {} ); // phpcs:ignore Emrikol.WordPress.NoHookClosure.ClosureHookCallback

// 2. Wrong code — error still expected (line 7).
add_action( 'init', function() {} ); // phpcs:ignore Some.Other.Sniff

// 3. Suppression with note — no error expected (line 10).
add_action( 'init', function() {} ); // phpcs:ignore Emrikol.WordPress.NoHookClosure.ClosureHookCallback -- legacy bootstrap

// 4. Parent sniff code — no error expected (line 13).
add_filter( 'the_title', fn( $t ) => strtoupper( $t ) ); // phpcs:ignore Emrikol.WordPress.NoHookClosure

// 5. No suppression — error expected (line 16).
add_action( 'init', function() {} );

// 6. Arrow function suppressed — no error expected (line 19).
add_filter( 'the_content', fn( $c ) => $c ); // phpcs:ignore Emrikol.WordPress.NoHookClosure.ClosureHookCallback
