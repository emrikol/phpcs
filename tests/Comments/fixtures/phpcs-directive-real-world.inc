<?php

// Directive text inside a string — should NOT be flagged.
$msg = 'phpcs:ignore is used for suppression';
$sql = "SELECT * FROM table; // phpcs:disable";
$heredoc = <<<EOT
// phpcs:ignore
phpcs:disable
EOT;

// Nested disable/enable for different sniffs — both matched.
// phpcs:disable Generic.Commenting.Todo
// phpcs:disable Generic.Formatting.SpaceAfterCast
$x = (int) $y; // @todo fix later
// phpcs:enable Generic.Formatting.SpaceAfterCast
// phpcs:enable Generic.Commenting.Todo

// Same sniff disabled twice — second overwrites first in tracker.
// phpcs:disable Squiz.Commenting.FunctionComment
$a = 1;
// phpcs:disable Squiz.Commenting.FunctionComment
$b = 2;
// phpcs:enable Squiz.Commenting.FunctionComment

// Enable for never-disabled sniff — should NOT cause error or crash.
// phpcs:enable Generic.WhiteSpace.DisallowTabIndent

// Category-level disable, sniff-level enable — category stays unmatched
// until the full category enable at end.
// phpcs:disable WordPress.Security
$data = $_GET['input'];
// phpcs:enable WordPress.Security.NonceVerification
// phpcs:enable WordPress.Security

// Disable with trailing comment content after the sniff codes.
// phpcs:disable PSR1.Files.SideEffects -- allow side effects here
echo 'hello';
// phpcs:enable PSR1.Files.SideEffects
