<?php
declare(strict_types=1);

namespace MyApp;

// These are PHP built-in classes — should be flagged when auto_detect is on
function get_date(DateTime $dt): void {}

function make_exception(): Exception {
	return new Exception('test');
}

$obj = new stdClass();

function get_pdo(): PDO {
	return new PDO('sqlite::memory:');
}

// These are NOT built-in — should NOT be flagged
function process(WP_Post $post): void {}
function handle(SomeCustomClass $obj): void {}
