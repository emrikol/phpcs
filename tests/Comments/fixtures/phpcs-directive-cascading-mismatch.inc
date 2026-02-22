<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

// --- Cascading error: clean disable + corrupted enable ---

// 1. Clean disable, corrupted enable (missing separator on enable).
//    Line 10: UnmatchedDisable for "Beta.Two" (enable didn't match).
//    Line 12: MissingNoteSeparator for "Beta.Two" (fixable).
//    Line 12: UnmatchedEnable warning for "Beta.Two done with suppression".
// phpcs:disable Beta.Two
$x = 1;
// phpcs:enable Beta.Two done with suppression

// 2. Corrupted disable, clean enable.
//    Line 18: MissingNoteSeparator for "Gamma.Three" (fixable).
//    Line 18: UnmatchedDisable for "Gamma.Three temporarily" (no matching enable).
//    Line 20: UnmatchedEnable warning for "Gamma.Three" (not in open_disables).
// phpcs:disable Gamma.Three temporarily
$y = 1;
// phpcs:enable Gamma.Three

// 3. Both corrupted identically — corrupted codes match each other.
//    Line 26: MissingNoteSeparator for "Delta.Four" (fixable).
//    Line 28: MissingNoteSeparator for "Delta.Four" (fixable).
//    No UnmatchedDisable — "Delta.Four oops" matches "Delta.Four oops".
// phpcs:disable Delta.Four oops
$z = 1;
// phpcs:enable Delta.Four oops
