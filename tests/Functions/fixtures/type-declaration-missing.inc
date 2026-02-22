<?php
declare(strict_types=1);

namespace MyApp;

// Line 7: error - missing return type, no docblock
function no_return_type() {
	return 'hello';
}

// Line 12: error - missing return type, no docblock
function another_missing() {
	return 42;
}

class Example {
	// Line 18: error - missing return type in method
	public function no_type_method() {
		return true;
	}

	// Line 23: error - missing return type on static method
	public static function static_no_type() {
		return [];
	}
}

// These should NOT error (excluded magic methods)
class MagicMethods {
	public function __construct() {}
	public function __destruct() {}
	public function __clone() {}
}
