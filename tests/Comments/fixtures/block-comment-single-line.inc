<?php

$a = 1;
/* single line block. */
$b = 2;

function emrikol_single_line_indent() {
	$c = 3;
	/* indented inside a function. */
	$d = 4;
}

$arr = array(
	'first'  => 1,
	/* inside an array literal. */
	'second' => 2,
);

/* translators: %s: thing. */
printf( esc_html__( 'Value: %s', 'emrikol' ), esc_html( $a ) );

$e = 5; /* trailing after code. */
$f = 6;

/* code follows. */ $g = 7;
