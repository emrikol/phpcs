<?php
declare(strict_types=1);

namespace MyApp;

function get_date(DateTime $dt): Exception {
	return new Exception('test');
}
