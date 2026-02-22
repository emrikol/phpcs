<?php
declare(strict_types=1);

namespace MyApp;

// Abstract method — still needs return type
abstract class Base {
	abstract public function do_something();
}

// Interface method — still needs return type
interface Doable {
	public function execute();
}

// Method with all modifiers
class Full {
	final protected static function locked() {
		return true;
	}
}

// Closure/anonymous function — not checked (T_CLOSURE, not T_FUNCTION)
$fn = function() {
	return 42;
};

// Arrow function not using T_FUNCTION, shouldn't be checked by this sniff
// (arrow functions use T_FN token which this sniff doesn't register for)
