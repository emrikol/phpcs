<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- test fixture

// 1. Basic correct usage with permission_callback (line 5).
register_rest_route( 'myplugin/v1', '/items', array(
	'methods'             => 'GET',
	'callback'            => 'my_get_items',
	'permission_callback' => 'my_permissions_check',
) );

// 2. Short array syntax with permission_callback (line 11).
register_rest_route( 'myplugin/v1', '/items', [
	'methods'             => 'GET',
	'callback'            => 'my_get_items',
	'permission_callback' => '__return_true',
] );

// 3. Permission callback as a closure (line 18).
register_rest_route( 'myplugin/v1', '/items', array(
	'methods'             => 'GET',
	'callback'            => 'my_get_items',
	'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
) );

// 4. Permission callback as an array method reference (line 25).
register_rest_route( 'myplugin/v1', '/items', array(
	'methods'             => 'GET',
	'callback'            => array( $this, 'get_items' ),
	'permission_callback' => array( $this, 'check_permissions' ),
) );

// 5. Multi-route format — all sub-arrays have permission_callback (line 31).
register_rest_route( 'myplugin/v1', '/items', array(
	array(
		'methods'             => 'GET',
		'callback'            => 'my_get_items',
		'permission_callback' => '__return_true',
	),
	array(
		'methods'             => 'POST',
		'callback'            => 'my_create_item',
		'permission_callback' => 'my_create_check',
	),
) );

// 6. Variable args — can't check statically, should not flag (line 43).
$args = get_rest_args();
register_rest_route( 'myplugin/v1', '/items', $args );

// 7. Function call args — can't check statically, should not flag (line 47).
register_rest_route( 'myplugin/v1', '/items', get_rest_args() );

// 8. Permission callback with double-quoted key (line 49).
register_rest_route( 'myplugin/v1', '/items', array(
	"methods"             => 'GET',
	"callback"            => 'my_get_items',
	"permission_callback" => 'my_check',
) );

// 9. Not a function call — should not flag (line 55).
$register_rest_route = 'some_value';

// 10. Method call — should not flag (line 58).
$obj->register_rest_route( 'ns', '/route', array( 'callback' => 'foo' ) );
