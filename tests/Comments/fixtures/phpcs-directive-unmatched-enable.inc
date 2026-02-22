<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

// --- Correct: no UnmatchedEnable warnings expected ---

// 1. Matched disable/enable pair.
// phpcs:disable Foo.Bar
$matched = 1;
// phpcs:enable Foo.Bar

// 2. Bare enable clears all (no warning).
// phpcs:disable Alpha.One
$bare_clear = 1;
// phpcs:enable

// 3. Matched multi-code disable, full enable.
// phpcs:disable Foo.Bar, Baz.Qux
$multi_matched = 1;
// phpcs:enable Foo.Bar
// phpcs:enable Baz.Qux

// --- Warnings: UnmatchedEnable ---

// 4. Enable for never-disabled code, no disables active (raw content path, line 25).
// phpcs:enable NeverDisabled.Sniff.Code

// 5. Enable for never-disabled code, another disable IS active (sniffCodes path, line 29).
// phpcs:disable Active.Sniff.Code
// phpcs:enable Unmatched.Other.Code
// phpcs:enable Active.Sniff.Code

// 6. Sub-sniff enable when only category was disabled (line 35).
// phpcs:disable WordPress.Security
$data = 1;
// phpcs:enable WordPress.Security.NonceVerification
// phpcs:enable WordPress.Security

// 7. Inline enable for never-disabled code (line 39).
$inline = 1; // phpcs:enable Inline.NeverDisabled.Code

// 8. Multiple codes in one enable, one matched, one unmatched (line 44 for Unmatched.Code).
// phpcs:disable Matched.Code
$partial = 1;
// phpcs:enable Matched.Code, Unmatched.Code
