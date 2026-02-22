<?php
declare(strict_types=1);

namespace MyApp;

// Line 7: error — matches /^WP_/ pattern
function get_post(WP_Post $post): void {}

// Line 10: error — matches /^WP_/ pattern
function get_query(): WP_Query {
	return new WP_Query();
}
// Line 14: error — matches /^Acme_/ pattern
$widget = new Acme_Widget();

// No error — doesn't match any pattern
function process(SomeOtherClass $obj): void {}
