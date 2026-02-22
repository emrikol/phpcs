<?php
// Inline phpcs:ignore suppression tests.

// 1. Correct inline suppression — no error expected.
function suppressed_missing_docblock( string $name ): void {} // phpcs:ignore Emrikol.Comments.DocblockTypeSync.MissingDocblock

// 2. Wrong code — error still expected.
function wrong_code_suppressed( string $name ): void {} // phpcs:ignore Some.Other.Sniff

// 3. Suppression with note — no error expected.
function suppressed_with_note( string $name ): void {} // phpcs:ignore Emrikol.Comments.DocblockTypeSync.MissingDocblock -- legacy API

// 4. Parent sniff code — no error expected.
function suppressed_parent_code( string $name ): void {} // phpcs:ignore Emrikol.Comments.DocblockTypeSync

// 5. No suppression — error expected.
function not_suppressed( string $name ): void {}
