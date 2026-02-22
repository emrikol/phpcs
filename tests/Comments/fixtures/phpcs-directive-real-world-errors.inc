<?php

// Category-level disable with only sub-sniff enable — category unmatched.
// phpcs:disable WordPress.Security
$data = $_GET['input'];
// phpcs:enable WordPress.Security.NonceVerification

// Legacy directive inside docblock (doc_comment tokens).
/** @codingStandardsIgnoreLine */
$y = 1;

// Legacy directive with leading spaces in comment.
//   @codingStandardsIgnoreLine
$z = 2;
