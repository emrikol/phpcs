<?php
declare(strict_types=1);

namespace MyApp;

// Line 7: error — unqualified DateTime in type hint
function get_date(DateTime $dt): void {}

// Line 10: error — unqualified Exception as return type
function make_exception(): Exception {
	return new Exception('test');
}
// Line 14: error — unqualified in new expression
$date = new DateTime();

// Line 17: error — unqualified in type hint AND return type
function transform(stdClass $input): PDO {
	return new PDO('sqlite::memory:');
}
