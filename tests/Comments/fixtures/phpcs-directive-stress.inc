<?php

// --- Section 1: Complex interleaving (no errors expected) ---

// Interleaved disable/enable for different sniffs — all matched.
// phpcs:disable Foo.Bar
// phpcs:disable Baz.Qux
$interleave_a = 1;
// phpcs:enable Foo.Bar
$interleave_b = 2;
// phpcs:enable Baz.Qux

// Disable/enable/disable/enable cycling for same sniff — all matched.
// phpcs:disable Foo.Bar
$cycle_a = 1;
// phpcs:enable Foo.Bar
// phpcs:disable Foo.Bar
$cycle_b = 2;
// phpcs:enable Foo.Bar

// Enable with codes that were never disabled — no error.
// phpcs:enable NonExistent.Sniff.Code

// Bare enable without any prior disables — no error.
// phpcs:enable

// Targeted ignore with multiple codes — not bare.
// phpcs:ignore Foo.Bar, Baz.Qux
$multi_ignore = 1;

// Disable with trailing comment text — should NOT be bare.
// phpcs:disable PSR1.Files.SideEffects -- allow side effects here
echo 'hello';
// phpcs:enable PSR1.Files.SideEffects

// Targeted disable, cleared by bare enable — no unmatched error.
// phpcs:disable Some.Sniff.Code
$cleared = 1;
// phpcs:enable

// Legacy directives in heredoc — should NOT be detected.
$doc = <<<EOT
// @codingStandardsIgnoreLine
phpcs:ignore
phpcs:disable
EOT;

// Legacy directives in nowdoc — should NOT be detected.
$ndoc = <<<'EOT'
// @codingStandardsIgnoreLine
phpcs:ignore
phpcs:disable
EOT;

// --- Section 2: Patterns that SHOULD produce errors ---

// Multiple consecutive bare ignores — each should be flagged (lines 58, 59).
// phpcs:ignore
// phpcs:ignore
$multi_bare = 1;

// Bare disable cleared by bare enable — BareDisable but no UnmatchedDisable (line 63).
// phpcs:disable
$bare_cleared = 1;
// phpcs:enable

// Multi-code disable, partial enable — unmatched for Alpha.Two (line 68).
// phpcs:disable Alpha.One, Alpha.Two
$partial = 1;
// phpcs:enable Alpha.One

// Hash-style legacy directive — should be caught (line 73).
# @codingStandardsIgnoreLine
$hash_legacy = 1;

// Suppression bypass: phpcs:ignore targeting our own BareIgnore,
// then bare ignore — bypass should still report (line 79).
// phpcs:ignore Emrikol.Comments.PhpcsDirective.BareIgnore
// phpcs:ignore
$bypass_attempt = 1;

// Suppression bypass: phpcs:ignore targeting UnmatchedDisable,
// then unmatched disable — bypass should still report (line 85).
// phpcs:ignore Emrikol.Comments.PhpcsDirective.UnmatchedDisable
// phpcs:disable Unmatched.After.Suppress
