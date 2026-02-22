<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

class MixedInlineSuppressionTest {
	// 1. Correct inline suppression — no warning expected (line 6).
	public mixed $suppressed; // phpcs:ignore Emrikol.PHP.DiscouragedMixedType.MixedPropertyType

	// 2. Wrong code — warning still expected (line 9).
	public mixed $wrong_code; // phpcs:ignore Some.Other.Sniff

	// 3. Suppression with note — no warning expected (line 12).
	public mixed $with_note; // phpcs:ignore Emrikol.PHP.DiscouragedMixedType.MixedPropertyType -- third-party API

	// 4. Parent sniff code — no warning expected (line 15).
	public mixed $parent_code; // phpcs:ignore Emrikol.PHP.DiscouragedMixedType

	// 5. No suppression — warning expected (line 18).
	public mixed $not_suppressed;
}

// 6. Function with mixed param/return inline-suppressed — no warning expected (line 22).
function suppressed_mixed( mixed $x ): mixed { return $x; } // phpcs:ignore Emrikol.PHP.DiscouragedMixedType

// 7. Function with mixed but wrong suppress code — warnings expected (line 25).
function wrong_suppress_mixed( mixed $x ): mixed { return $x; } // phpcs:ignore Some.Other.Sniff
