<?php

// Property with attribute — should still flag if untyped.
class WithAttributes {
	#[ORM\Column(type: 'string')]
	public $attributed_untyped;

	#[ORM\Column(type: 'string')]
	public string $attributed_typed;
}

// Property with docblock only — should still flag (docblock is not a type).
class DocblockOnly {
	/** @var string */
	public $docblock_untyped;

	/** @var string */
	public string $docblock_typed;
}

// Property with new in default value (PHP 8.1) — should flag if untyped.
class NewInDefault {
	public $untyped_new = null;
	public \ArrayObject $typed_new;
}

// Multiple classes in one file — all checked independently.
class FirstClass {
	public $first_untyped;
	public string $first_typed;
}

class SecondClass {
	public $second_untyped;
	protected int $second_typed;
}

// Class inside function — properties should still be checked.
function create_class(): object {
	return new class {
		public $nested_untyped;
		public string $nested_typed;
	};
}

// Deeply nested: class inside method inside class.
class Outer {
	public $outer_untyped;

	public function factory(): object {
		return new class {
			public $inner_untyped;
			public int $inner_typed;
		};
	}
}

// Property with multi-line attribute — should still flag if untyped.
class MultiLineAttr {
	#[Attribute(
		name: 'foo',
		value: 'bar',
	)]
	public $multiline_attr_untyped;

	#[Attribute(name: 'baz')]
	public string $multiline_attr_typed;
}

// Property after long docblock.
class LongDocblock {
	/**
	 * This is a very long docblock that describes the property.
	 *
	 * It spans multiple lines and includes all sorts of tags.
	 *
	 * @var string The name of the thing.
	 * @since 1.0
	 */
	public $long_docblock_untyped;

	public string $long_docblock_typed;
}

// Static properties with attributes.
class StaticAttrs {
	#[Deprecated]
	public static $static_untyped;

	#[Deprecated]
	public static string $static_typed;
}
