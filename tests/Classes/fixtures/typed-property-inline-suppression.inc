<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

class InlineSuppressionTest {
	// 1. Correct inline suppression — no error expected (line 6).
	public $suppressed; // phpcs:ignore Emrikol.Classes.TypedProperty.MissingPropertyType

	// 2. Wrong code — error still expected (line 9).
	public $wrong_code; // phpcs:ignore Some.Other.Sniff

	// 3. Suppression with note — no error expected (line 12).
	public $with_note; // phpcs:ignore Emrikol.Classes.TypedProperty.MissingPropertyType -- legacy property

	// 4. Parent sniff code — no error expected (line 15).
	public $parent_code; // phpcs:ignore Emrikol.Classes.TypedProperty

	// 5. No suppression — error expected (line 18).
	public $not_suppressed;

	// 6. Typed property — no error regardless (line 21).
	public string $typed;
}
