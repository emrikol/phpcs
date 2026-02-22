<?php
declare(strict_types=1);

namespace MyApp;

function greet(string $name): string {
	return "Hello, $name";
}

function add(int $a, int $b): int {
	return $a + $b;
}

function process(array $items, callable $callback): void {}

function nullable(?string $value): void {}

class Example {
	public function method(float $rate, bool $active): void {}
	public static function create(object $data): self { return new self(); }
}
