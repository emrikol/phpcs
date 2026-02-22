<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that all functions have return type declarations.
 * Supports auto-fixing from @return docblock tags via phpcbf.
 *
 * @package Emrikol\Sniffs\Functions
 */

namespace Emrikol\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class TypeDeclarationSniff
 *
 * Checks that functions have return type declarations. Optionally validates
 * that the declared return type is a known/valid type. When a @return docblock
 * tag exists with a clean PHP type, phpcbf can auto-fix the missing declaration.
 */
class TypeDeclarationSniff implements Sniff {

	/**
	 * Whether to validate that return types are known/valid types.
	 *
	 * When false (default), only checks that a return type exists.
	 * When true, also validates the type against built-in PHP types
	 * and the $known_classes list.
	 *
	 * @var bool
	 */
	public $validate_types = false;

	/**
	 * Additional known class names considered valid return types.
	 *
	 * Populate via ruleset.xml or by including a preset file
	 * (e.g., presets/wordpress-classes.xml).
	 *
	 * @var array
	 */
	public $known_classes = array();

	/**
	 * Magic methods that should not have a return type declaration.
	 *
	 * @var array
	 */
	private $excluded_methods = array(
		'__construct',
		'__destruct',
		'__clone',
	);

	/**
	 * Docblock type aliases mapped to their PHP equivalents.
	 *
	 * @var array
	 */
	private $type_aliases = array(
		'integer'  => 'int',
		'boolean'  => 'bool',
		'double'   => 'float',
		'callback' => 'callable',
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_FUNCTION );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ): void {
		$tokens        = $phpcs_file->getTokens();
		$function_name = $phpcs_file->getDeclarationName( $stack_ptr );

		// Skip excluded magic methods.
		if ( in_array( strtolower( $function_name ), $this->excluded_methods, true ) ) {
			return;
		}

		// Find the closing parenthesis of the function signature.
		$function_declaration_end = $tokens[ $stack_ptr ]['parenthesis_closer'];

		// Find the colon that precedes the return type declaration.
		$colon_ptr = $phpcs_file->findNext( T_COLON, $function_declaration_end, $function_declaration_end + 3 );

		if ( false === $colon_ptr ) {
			$error_message = "Missing return type declaration for function '$function_name'";

			// Try to resolve a clean PHP type from the docblock.
			$docblock_type = $this->get_return_type_from_docblock( $phpcs_file, $stack_ptr );
			$php_type      = $this->normalize_docblock_type( $docblock_type );

			if ( null !== $php_type ) {
				$fix = $phpcs_file->addFixableError( $error_message, $stack_ptr, 'MissingReturnType' );

				if ( $fix ) {
					$phpcs_file->fixer->addContent( $function_declaration_end, ': ' . $php_type );
				}
			} else {
				$phpcs_file->addError( $error_message, $stack_ptr, 'MissingReturnType' );
			}

			return;
		}

		// Optionally validate the return type.
		if ( $this->validate_types ) {
			$return_type_ptr     = $colon_ptr + 1;
			$return_type_end     = $phpcs_file->findNext( array( T_SEMICOLON, T_OPEN_CURLY_BRACKET ), $return_type_ptr ) - 1;
			$return_type_content = trim( $phpcs_file->getTokensAsString( $return_type_ptr, ( $return_type_end - $return_type_ptr + 1 ) ) );

			if ( ! $this->is_valid_return_type( $return_type_content ) ) {
				$phpcs_file->addError(
					"Invalid return type declaration '$return_type_content' for function '$function_name'",
					$stack_ptr,
					'InvalidReturnType'
				);
			}
		}
	}

	/**
	 * Extracts the return type from the function's docblock @return tag.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the function token.
	 *
	 * @return string|null The docblock return type string, or null if not found.
	 */
	private function get_return_type_from_docblock( File $phpcs_file, int $stack_ptr ): ?string {
		$tokens = $phpcs_file->getTokens();

		// Find the docblock close tag before the function.
		$doc_close = $phpcs_file->findPrevious(
			array( T_DOC_COMMENT_CLOSE_TAG ),
			$stack_ptr - 1
		);

		if ( false === $doc_close ) {
			return null;
		}

		// Ensure the docblock belongs to this function.
		$allowed_between = array(
			T_WHITESPACE,
			T_PUBLIC,
			T_PROTECTED,
			T_PRIVATE,
			T_STATIC,
			T_ABSTRACT,
			T_FINAL,
		);

		for ( $i = $doc_close + 1; $i < $stack_ptr; $i++ ) {
			if ( ! in_array( $tokens[ $i ]['code'], $allowed_between, true ) ) {
				return null;
			}
		}

		$doc_open = $tokens[ $doc_close ]['comment_opener'];

		// Scan the docblock for @return tag.
		for ( $i = $doc_open; $i <= $doc_close; $i++ ) {
			if ( T_DOC_COMMENT_TAG !== $tokens[ $i ]['code'] || '@return' !== $tokens[ $i ]['content'] ) {
				continue;
			}

			// The next DOC_COMMENT_STRING should contain the type.
			$string_ptr = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $i + 1, $doc_close );
			if ( false === $string_ptr ) {
				continue;
			}

			$parts = preg_split( '/\s+/', trim( $tokens[ $string_ptr ]['content'] ), 2 );
			if ( ! empty( $parts[0] ) ) {
				return $parts[0];
			}
		}

		return null;
	}

	/**
	 * Normalizes a docblock type string into a valid PHP type hint.
	 *
	 * Returns null if the type is too complex to auto-fix safely.
	 * Conservative strategy: only fixes single types and Type|null.
	 *
	 * @param string|null $docblock_type The raw docblock type string.
	 *
	 * @return string|null A valid PHP type hint, or null if not auto-fixable.
	 */
	private function normalize_docblock_type( ?string $docblock_type ): ?string {
		if ( null === $docblock_type || '' === $docblock_type ) {
			return null;
		}

		$type = trim( $docblock_type );

		// Handle array notations: type[], array<...>.
		if ( preg_match( '/\[\]$/', $type ) || preg_match( '/^array\s*</', $type ) ) {
			return 'array';
		}

		// Handle nullable shorthand: ?Type.
		if ( 0 === strpos( $type, '?' ) ) {
			$inner = $this->resolve_simple_type( substr( $type, 1 ) );
			return null !== $inner ? '?' . $inner : null;
		}

		// Handle union types.
		if ( false !== strpos( $type, '|' ) ) {
			$parts = explode( '|', $type );

			// Only auto-fix Type|null or null|Type.
			if ( 2 !== count( $parts ) ) {
				return null;
			}

			$parts = array_map( 'trim', $parts );
			if ( 'null' === strtolower( $parts[0] ) ) {
				$resolved = $this->resolve_simple_type( $parts[1] );
				return null !== $resolved ? '?' . $resolved : null;
			} elseif ( 'null' === strtolower( $parts[1] ) ) {
				$resolved = $this->resolve_simple_type( $parts[0] );
				return null !== $resolved ? '?' . $resolved : null;
			}

			return null;
		}

		return $this->resolve_simple_type( $type );
	}

	/**
	 * Resolves a single type name, applying aliases.
	 *
	 * @param string $type A single type name.
	 *
	 * @return string|null The resolved PHP type, or null if not recognized.
	 */
	private function resolve_simple_type( string $type ): ?string {
		$type = trim( $type );

		// Apply aliases.
		$lower = strtolower( $type );
		if ( isset( $this->type_aliases[ $lower ] ) ) {
			return $this->type_aliases[ $lower ];
		}

		// Known PHP built-in types.
		$builtin = array(
			'string', 'int', 'float', 'bool',
			'array', 'callable', 'iterable', 'object',
			'void', 'mixed', 'never', 'null',
			'self', 'parent', 'static', 'false', 'true',
		);

		if ( in_array( $lower, $builtin, true ) ) {
			return $lower;
		}

		// Assume it's a class name if it looks like an identifier.
		if ( preg_match( '/^[a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*$/', $type ) ) {
			return $type;
		}

		return null;
	}

	/**
	 * Checks if the given return type is valid.
	 *
	 * Validates against built-in PHP types and the configured known_classes list.
	 *
	 * @param string $return_type The return type string to validate.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_return_type( string $return_type ): bool {
		$valid_types = array(
			'bool', 'int', 'float', 'string',
			'array', 'callable', 'iterable', 'object',
			'void', 'mixed', 'never', 'null',
			'self', 'parent', 'static', 'false', 'true',
		);

		$types = explode( '|', str_replace( '?', '', $return_type ) );

		foreach ( $types as $type ) {
			$type = ltrim( trim( $type ), '\\' );

			if ( in_array( $type, $valid_types, true ) ) {
				continue;
			}

			if ( in_array( $type, $this->known_classes, true ) ) {
				continue;
			}

			return false;
		}

		return true;
	}
}
