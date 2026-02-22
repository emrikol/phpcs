<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

// --- Correct: no MissingNoteSeparator errors expected ---

// 1. Targeted ignore with proper -- separator.
// phpcs:ignore Foo.Bar -- proper note here
$correct_note = 1;

// 2. Multi-code with proper -- separator.
// phpcs:ignore Foo.Bar, Baz.Qux -- multi-code with note
$correct_multi = 1;

// 3. No note at all — fine.
// phpcs:ignore Foo.Bar
$no_note = 1;

// 4. Disable/enable with proper separator.
// phpcs:disable PSR1.Files.SideEffects -- allow side effects
echo 'hello';
// phpcs:enable PSR1.Files.SideEffects

// --- Errors: MissingNoteSeparator ---

// 5. Single code, missing separator — fixable (line 26).
// phpcs:ignore Foo.Bar this note lacks the separator
$missing_single = 1;

// 6. Multi-code, last one missing separator — fixable (line 30).
// phpcs:ignore Foo.Bar, Baz.Qux reason goes here
$missing_multi = 1;

// 7. Disable missing separator — fixable (line 34).
// phpcs:disable Alpha.One temporarily disabling
$missing_disable = 1;
// 8. Enable missing separator — fixable (line 37). Same corrupted code clears the disable.
// phpcs:enable Alpha.One temporarily disabling

// 9. Block comment missing separator — fixable (line 40).
/* phpcs:ignore Gamma.Three some note here */
$missing_block = 1;

// 10. Hash comment missing separator — fixable (line 44).
# phpcs:ignore Delta.Four hash note
$missing_hash = 1;

// 11. Tab instead of space before note — fixable (line 48).
// phpcs:ignore Epsilon.Five	tab note here
$tab_note = 1;

// 12. Tab between codes (no comma) — fixable (line 52).
// phpcs:ignore Zeta.Six	Zeta.Seven
$tab_between = 1;

// 13. Multiple corrupted codes — NOT auto-fixable (line 56).
// phpcs:ignore Eta.Eight note, Theta.Nine note2
$multi_corrupt = 1;

// --- Errors: MalformedNoteSeparator ---

// 14. -- without any spaces — fixable (line 62).
// phpcs:ignore Iota.Ten--note no spaces around dashes
$malformed_none = 1;

// 15. -- with space after but not before — fixable (line 66).
// phpcs:ignore Kappa.Eleven-- note after only
$malformed_after = 1;

// 16. Inline ignore with missing separator — fixable (line 70).
$inline_missing = 1; // phpcs:ignore Lambda.Twelve inline note here

// 17. Inline ignore with malformed separator — fixable (line 73).
$inline_malformed = 1; // phpcs:ignore Mu.Thirteen--inline note
