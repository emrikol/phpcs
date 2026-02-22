<?php
declare(strict_types=1);

namespace MyApp;

// Valid built-in types — no errors regardless of settings (lines 7-24)
function valid_string(): string { return ''; }
function valid_int(): int { return 0; }
function valid_float(): float { return 0.0; }
function valid_bool(): bool { return true; }
function valid_array(): array { return []; }
function valid_void(): void {}
function valid_mixed(): mixed { return null; }
function valid_never(): never { throw new \Exception(); }
function valid_null(): null { return null; }
function valid_callable(): callable { return function() {}; }
function valid_false(): false { return false; }
function valid_true(): true { return true; }
function valid_iterable(): iterable { return []; }
function valid_nullable(): ?string { return null; }
function valid_union(): int|string { return 1; }
function valid_object(): object { return new \stdClass(); }
function valid_string2(): string { return ''; }
function valid_int2(): int { return 0; }

// Auto-detected built-in class — valid when auto_detect is on
function returns_datetime(): DateTime { return new \DateTime(); }
function returns_exception(): Exception { return new \Exception(); }

// Fully-qualified built-in class — always valid (stripped of leading backslash)
function returns_fq_datetime(): \DateTime { return new \DateTime(); }

// Unknown class — invalid when validate_types is on (without known_classes)
function returns_unknown(): FooBarBaz { return new \FooBarBaz(); }
function returns_unknown2(): SomeVendorClass { return new \SomeVendorClass(); }
