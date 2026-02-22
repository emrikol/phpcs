<?php
declare(strict_types=1);

namespace MyApp;

use DateTime;
use Exception;

// Imported — OK
function get_date(DateTime $dt): DateTime {
	return $dt;
}

// Fully qualified with backslash — OK
function make_exception(): \Exception {
	return new \Exception('test');
}

// Qualified via backslash prefix — OK
$date = new \DateTime();

// Not a known global class — OK (not in list)
function process(SomeLocalClass $obj): void {}
