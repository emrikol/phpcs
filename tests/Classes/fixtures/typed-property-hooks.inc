<?php

// PHP 8.4 property hooks — variables inside hook bodies must NOT be flagged.

class BlockHooks {
	// Block-style get/set hooks — $this and $value inside bodies are NOT properties.
	public string $name {
		get { return $this->name; }
		set(string $value) { $this->name = $value; }
	}

	// Get-only hook.
	public int $computed {
		get { return $this->a + $this->b; }
	}

	// Untyped property with hooks — the property itself SHOULD be flagged (line 18).
	public $untyped_hooked {
		get { return $this->untyped_hooked; }
	}

	// Regular typed properties around hooks — no errors.
	private int $a = 1;
	private int $b = 2;
}

class ArrowHooks {
	// Arrow-style get/set hooks — $this and $value inside are NOT properties.
	public string $short {
		get => $this->short;
		set => $this->short = strtolower($value);
	}
}

class MixedHookStyles {
	// Block get, arrow set.
	public string $mixed_style {
		get { return $this->mixed_style; }
		set => $this->mixed_style = strtolower($value);
	}
}

class PropertiesAfterHooks {
	// Property with hooks.
	public string $hooked {
		get { return $this->hooked; }
	}

	// Untyped property AFTER a hooked property — SHOULD be flagged (line 50).
	public $after_hooks;

	// Typed property AFTER hooks — no error.
	public int $typed_after = 42;
}

class HookWithComplexBody {
	// Hook body with multiple statements and local-like variables.
	public string $formatted {
		set(string $value) {
			$trimmed = trim($value);
			$this->formatted = strtolower($trimmed);
		}
	}
}

class NestedClassWithHooks {
	// Outer class property — untyped, SHOULD be flagged (line 68).
	public $outer_untyped;

	public string $outer_hooked {
		get { return $this->outer_hooked; }
	}

	public function factory(): object {
		// Inner anonymous class with hooks.
		return new class {
			public string $inner_hooked {
				get { return $this->inner_hooked; }
			}

			// Untyped in inner class — SHOULD be flagged (line 82).
			public $inner_untyped;
		};
	}
}
