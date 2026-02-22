<?php
declare(strict_types=1);

namespace MyApp;

function get_name(): string {
	return 'hello';
}

function get_count(): int {
	return 42;
}

function process(): void {}

function get_items(): array {
	return [];
}

function get_rate(): float {
	return 3.14;
}

function is_active(): bool {
	return true;
}

function get_handler(): callable {
	return function() {};
}

class Example {
	public function __construct() {}
	public function __destruct() {}
	public function __clone() {}
	public function method(): string { return ''; }
	public static function create(): self { return new self(); }
	public function parent_ref(): static { return $this; }
}

function get_nullable(): ?string {
	return null;
}

function get_union(): int|string {
	return 42;
}

function get_mixed(): mixed {
	return null;
}

function never_returns(): never {
	throw new \Exception();
}
