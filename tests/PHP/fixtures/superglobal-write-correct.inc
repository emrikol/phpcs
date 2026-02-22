<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- test fixture

// 1. Reading superglobals — all OK.
$name = $_GET['name'];
$data = $_POST['data'];
$any  = $_REQUEST['key'];
$host = $_SERVER['HTTP_HOST'];
$sid  = $_COOKIE['session_id'];
$sess = $_SESSION['user'];
$file = $_FILES['upload'];
$path = $_ENV['PATH'];

// 2. Passing superglobals to functions — OK.
my_function( $_GET['key'] );
sanitize_text_field( $_POST['input'] );

// 3. Using superglobals in conditions — OK.
if ( isset( $_GET['action'] ) ) {
	do_something();
}

// 4. Using superglobals in string interpolation — OK.
$url = "https://example.com?q={$_GET['q']}";

// 5. Array access on read — OK.
$val = $_GET['a']['b']['c'];

// 6. Comparison — OK.
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	process();
}

// 7. Regular variables can be assigned — OK.
$my_var = 'hello';
$data   = array( 'key' => 'value' );
