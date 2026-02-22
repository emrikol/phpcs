<?php

class CorrectProperties {
	public string $name;
	protected int $age;
	private bool $active;
	public float $score = 0.0;
	public array $items = array();
	public ?string $nickname = null;
	public readonly string $id;
	public string|int $flexible;
}
