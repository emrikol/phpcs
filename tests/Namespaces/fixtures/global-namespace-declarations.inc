<?php
declare(strict_types=1);

namespace WP_TimeSync;

// Line 6: class declaration — NOT a reference, no error.
class WP_TimeSync_Plugin {
	// Line 8: new expression — IS a reference, error.
	public function init(): void {
		$post = new WP_Post();
	}
}

// Line 14: interface declaration — NOT a reference, no error.
interface WP_TimeSync_Interface {
	public function run(): void;
}

// Line 19: trait declaration — NOT a reference, no error.
trait WP_TimeSync_Trait {
	public function helper(): void {}
}

// Line 24: enum declaration — NOT a reference, no error.
enum WP_TimeSync_Status {
	case Active;
	case Inactive;
}

// Line 30: extends — IS a reference, error.
class My_Child extends WP_Widget {}

// Line 33: implements — IS a reference, error.
class My_Other implements WP_Interface {}

// Line 36: type hint — IS a reference, error.
function process( WP_Post $post ): void {}

// Line 39: return type — IS a reference, error.
function get_query(): WP_Query {
	// Line 41: new expression — IS a reference, error.
	return new WP_Query();
}
