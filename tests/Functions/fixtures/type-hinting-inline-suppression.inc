<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- suppress strict types

// 1. Correct inline suppression — no error expected (line 5).
function suppressed_param( $x ): void {} // phpcs:ignore Emrikol.Functions.TypeHinting.MissingParameterType

// 2. Wrong code — error still expected (line 8).
function wrong_code_param( $x ): void {} // phpcs:ignore Some.Other.Sniff

// 3. Suppression with note — no error expected (line 11).
function suppressed_with_note( $x ): void {} // phpcs:ignore Emrikol.Functions.TypeHinting.MissingParameterType -- legacy API

// 4. Parent sniff code — no error expected (line 14).
function suppressed_parent_code( $x ): void {} // phpcs:ignore Emrikol.Functions.TypeHinting

// 5. No suppression — error expected (line 17).
function not_suppressed( $x ): void {}
