<?php
// Functions with no docblock at all.

function no_docblock_typed_param( string $name ): void {
}

function no_docblock_typed_return(): string {
	return 'hello';
}

function no_docblock_both( string $name, int $count ): bool {
	return strlen( $name ) > $count;
}

class NoDocblockClass {

	public function method_no_doc( string $value ): int {
		return strlen( $value );
	}

	public static function static_no_doc( array $items ): void {
	}
}

function no_docblock_nullable( ?string $value ): ?int {
	return null === $value ? null : strlen( $value );
}

function no_docblock_union( int|string $value ): void {
}

function no_docblock_void_only(): void {
}
