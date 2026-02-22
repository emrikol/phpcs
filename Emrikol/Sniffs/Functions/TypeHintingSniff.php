<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that all function parameters have type hints.
 * Supports auto-fixing from @param docblock tags via phpcbf.
 *
 * @package Emrikol\Sniffs\Functions
 */

namespace Emrikol\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class TypeHintingSniff
 *
 * Checks that function parameters have type hints. When a @param docblock
 * tag exists with a clean PHP type, phpcbf can auto-fix the missing hint.
 */
class TypeHintingSniff implements Sniff {

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
		$parameters    = $phpcs_file->getMethodParameters( $stack_ptr );
		$function_name = $phpcs_file->getDeclarationName( $stack_ptr );

		foreach ( $parameters as $param ) {
			if ( '' !== $param['type_hint'] ) {
				continue;
			}

			$error_message = "Missing type declaration for parameter '{$param['name']}' in function '$function_name'";

			// Try to resolve a clean PHP type from the docblock.
			$docblock_type = $this->get_param_type_from_docblock( $phpcs_file, $stack_ptr, $param['name'] );
			$php_type      = $this->normalize_docblock_type( $docblock_type );

			if ( null !== $php_type ) {
				$fix = $phpcs_file->addFixableError( $error_message, $stack_ptr, 'MissingParameterType' );

				if ( $fix ) {
					// Determine the token to insert before (handle & and ... prefixes).
					$insert_before = $param['token'];
					if ( ! empty( $param['pass_by_reference'] ) && false !== $param['reference_token'] ) {
						$insert_before = $param['reference_token'];
					} elseif ( ! empty( $param['variable_length'] ) && false !== $param['variadic_token'] ) {
						$insert_before = $param['variadic_token'];
					}

					$phpcs_file->fixer->addContentBefore( $insert_before, $php_type . ' ' );
				}
			} else {
				$phpcs_file->addError( $error_message, $stack_ptr, 'MissingParameterType' );
			}
		}
	}

	/**
	 * Extracts the type for a specific parameter from the function's docblock.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $stack_ptr  The position of the function token.
	 * @param string $param_name The parameter name including the $ prefix.
	 *
	 * @return string|null The docblock type string, or null if not found.
	 */
	private function get_param_type_from_docblock( File $phpcs_file, int $stack_ptr, string $param_name ): ?string {
		$tokens = $phpcs_file->getTokens();

		// Find the docblock close tag before the function.
		$doc_close = $phpcs_file->findPrevious(
			array( T_DOC_COMMENT_CLOSE_TAG ),
			$stack_ptr - 1
		);

		if ( false === $doc_close ) {
			return null;
		}

		// Ensure the docblock belongs to this function (only whitespace and
		// modifiers between it and the function keyword).
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

		// Scan the docblock for @param tags.
		for ( $i = $doc_open; $i <= $doc_close; $i++ ) {
			if ( T_DOC_COMMENT_TAG !== $tokens[ $i ]['code'] || '@param' !== $tokens[ $i ]['content'] ) {
				continue;
			}

			// The next DOC_COMMENT_STRING should contain "Type $variable_name ...".
			$string_ptr = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $i + 1, $doc_close );
			if ( false === $string_ptr ) {
				continue;
			}

			$parts = preg_split( '/\s+/', trim( $tokens[ $string_ptr ]['content'] ), 3 );
			if ( count( $parts ) >= 2 && $parts[1] === $param_name ) {
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

		// Handle nullable shorthand already in docblock: ?Type.
		if ( 0 === strpos( $type, '?' ) ) {
			$inner = $this->resolve_simple_type( substr( $type, 1 ) );
			return null !== $inner ? '?' . $inner : null;
		}

		// Handle union types.
		if ( false !== strpos( $type, '|' ) ) {
			$parts = explode( '|', $type );

			// Only auto-fix Type|null or null|Type (exactly two parts, one is null).
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
}
