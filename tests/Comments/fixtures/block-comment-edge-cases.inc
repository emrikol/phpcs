<?php

// This comment has */ in the text.
// Second line of star-slash group.
$star_slash = 1;

//
//
$bare_slashes = 2;

// Ünïcödé characters: émojis 🎉 and spëcial ñ chars.
// Second line with more Unicode: 日本語テスト.
$unicode = 3;

// This is an extremely long comment line that goes on and on and on and on and on and on and on and on and on and on and on and on and on and on and on to test correctness.
// Second long line here for the block.
$long_line = 4;

// Before an interface.
// More interface docs.
interface TestInterface {
	// Before a const.
	// More const docs.
	const FOO = 1;
}

// Before a trait.
// More trait docs.
trait TestTrait {
	// Before an abstract method.
	// More abstract docs.
	abstract public function do_thing(): void;
}

// Before a final class.
// More final docs.
final class FinalClass {
	// Before a static method.
	// More static docs.
	public static function create(): self {
		return new self();
	}
}

// Before an enum.
// More enum docs.
enum TestEnum {
	case Foo;
}

// Anonymous class comment.
// More anonymous docs.
$anon = new class {
	public function test(): void {}
};

// Closure comment.
// More closure docs.
$closure = function () {
	return true;
};

// Regular comment before assignment.
// Second line.
$not_a_decl = 'hello';

// First isolated.
$between1 = 1;
// Second isolated.
$between2 = 2;
// Third isolated.
$between3 = 3;

$a1 = 1; // Inline one.
$a2 = 2; // Inline two.
$a3 = 3; // Inline three.

// See https://example.com/path for details.
// Another line about the URL.
$url_comment = 5;

// Attribute docs.
// More attribute docs.
#[SomeAttribute]
function attributed_func(): void {}

function nested_func() {
	if ( true ) {
		while ( true ) {
			// Deeply nested first.
			// Deeply nested second.
			break;
		}
	}
}

class ReadonlyClass {
	// Before readonly.
	// More readonly docs.
	public readonly string $name;

	public function __construct( string $name ) {
		$this->name = $name;
	}
}

class VarClass {
	// Before var property.
	// More var docs.
	var $legacy_prop = 0;
}

# Hash first line.
// Slash second line.
$hash_slash_mix = 6;

// This has /* opening syntax.
// More about the opening.
$open_syntax = 7;

// First line no indent.
	// Second line tabbed.
$mixed_indent = 8;

// First line with text.
//
// Third line with text.
$middle_bare = 9;
