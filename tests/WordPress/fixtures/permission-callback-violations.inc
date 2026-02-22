<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- test fixture

// 1. Missing permission_callback — long array syntax (line 5).
register_rest_route( 'myplugin/v1', '/items', array(
	'methods'  => 'GET',
	'callback' => 'my_get_items',
) );

// 2. Missing permission_callback — short array syntax (line 11).
register_rest_route( 'myplugin/v1', '/items', [
	'methods'  => 'GET',
	'callback' => 'my_get_items',
] );

// 3. Multi-route — first has it, second missing (line 17).
register_rest_route( 'myplugin/v1', '/items', array(
	array(
		'methods'             => 'GET',
		'callback'            => 'my_get_items',
		'permission_callback' => '__return_true',
	),
	array(
		'methods'  => 'POST',
		'callback' => 'my_create_item',
	),
) );

// 4. Multi-route — both missing (line 29).
register_rest_route( 'myplugin/v1', '/items', array(
	array(
		'methods'  => 'GET',
		'callback' => 'my_get_items',
	),
	array(
		'methods'  => 'POST',
		'callback' => 'my_create_item',
	),
) );

// 5. Empty args array (line 40).
register_rest_route( 'myplugin/v1', '/items', array() );

// 6. Empty short array args (line 43).
register_rest_route( 'myplugin/v1', '/items', [] );

// 7. Multi-route with short array — missing (line 46).
register_rest_route( 'myplugin/v1', '/items', [
	[
		'methods'  => 'GET',
		'callback' => 'my_get_items',
	],
] );

// 8. Namespaced call — still flagged (line 53).
\register_rest_route( 'myplugin/v1', '/items', array(
	'methods'  => 'GET',
	'callback' => 'my_get_items',
) );
