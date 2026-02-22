<?php
declare(strict_types=1);

namespace MyApp;

use DateTime;
use Exception;
use PDO;
use stdClass;

// All imported — no errors expected even though they're known globals
function get_date(DateTime $dt): DateTime {
	return $dt;
}

function make_exception(): Exception {
	return new Exception('test');
}

$obj = new stdClass();
$pdo = new PDO('sqlite::memory:');
