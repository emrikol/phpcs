<?php

class PropertyHookMixed {
	// mixed as set parameter type — should warn (line 6).
	public string $name {
		set(mixed $value) { $this->name = (string) $value; }
	}

	// mixed as property type with hooks — should warn on property (line 11),
	// and on set param (line 13).
	public mixed $flexible {
		get { return $this->flexible; }
		set(mixed $value) { $this->flexible = $value; }
	}

	// Non-mixed set parameter — should NOT warn.
	public string $strict {
		set(string $value) { $this->strict = $value; }
	}

	// Set hook without parameter type — should NOT warn.
	public string $untyped_set {
		set($value) { $this->untyped_set = (string) $value; }
	}

	// Arrow-style hooks — no set parameter, should NOT warn.
	public string $arrow {
		get => $this->arrow;
		set => $this->arrow = strtolower($value);
	}
}
