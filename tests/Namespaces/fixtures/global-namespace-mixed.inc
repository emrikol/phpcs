<?php
declare(strict_types=1);

namespace MyApp;

use DateTime;

// OK — DateTime is imported
function imported_ok(DateTime $dt): DateTime {
	return $dt;
}

// Line 14: error — Exception is NOT imported and unqualified
function not_imported(): Exception {
	return new Exception('error');
}

// OK — fully qualified
function qualified_ok(): \stdClass {
	return new \stdClass();
}
// Line 23: error — stdClass unqualified on this line (not qualified, not imported)
$obj = new stdClass();
