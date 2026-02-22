<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures that docblocks match code-side type declarations.
 * Auto-generates missing docblocks and fixes incomplete tags.
 *
 * @package Emrikol\Sniffs\Comments
 */

namespace Emrikol\Sniffs\Comments;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class DocblockTypeSyncSniff
 *
 * Syncs docblock @param/@return tags with code-side type declarations.
 * If a function has typed parameters or a return type, the docblock should
 * reflect those types. Creates missing docblocks, adds missing tags, fills
 * in missing types, and warns on type drift.
 */
class DocblockTypeSyncSniff implements Sniff {

	/**
	 * Whether to generate a full docblock when none exists.
	 *
	 * @var bool
	 */
	public $generate_missing_docblocks = true;

	/**
	 * Whether to report TypeDrift warnings.
	 *
	 * @var bool
	 */
	public $report_type_drift = true;

	/**
	 * Tokens allowed between a docblock and the function keyword.
	 *
	 * @var array
	 */
	private $allowed_between = array(
		T_WHITESPACE,
		T_PUBLIC,
		T_PROTECTED,
		T_PRIVATE,
		T_STATIC,
		T_ABSTRACT,
		T_FINAL,
	);

	/**
	 * Magic methods that should not have @return tags.
	 *
	 * @var array
	 */
	private $no_return_methods = array(
		'__construct',
		'__destruct',
		'__clone',
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
		$tokens     = $phpcs_file->getTokens();
		$parameters = $phpcs_file->getMethodParameters( $stack_ptr );
		$return_type = $this->get_return_type_from_code( $phpcs_file, $stack_ptr );

		// Build list of typed params.
		$typed_params = array();
		foreach ( $parameters as $param ) {
			if ( '' !== $param['type_hint'] ) {
				$typed_params[] = $param;
			}
		}

		// If nothing is typed, skip entirely.
		if ( empty( $typed_params ) && '' === $return_type ) {
			return;
		}

		$function_name = $phpcs_file->getDeclarationName( $stack_ptr );
		$is_no_return  = in_array( strtolower( (string) $function_name ), $this->no_return_methods, true );

		// Find the function's docblock.
		$doc_close = $this->find_docblock( $phpcs_file, $stack_ptr );

		if ( false === $doc_close ) {
			// No docblock at all.
			if ( $this->generate_missing_docblocks ) {
				$this->handle_missing_docblock( $phpcs_file, $stack_ptr, $typed_params, $return_type, $is_no_return );
			}
			return;
		}

		$doc_open = $tokens[ $doc_close ]['comment_opener'];

		// Check for {@inheritdoc}.
		if ( $this->has_inheritdoc( $phpcs_file, $doc_open, $doc_close ) ) {
			return;
		}

		// Parse existing docblock tags.
		$existing_params = $this->parse_param_tags( $phpcs_file, $doc_open, $doc_close );
		$existing_return = $this->parse_return_tag( $phpcs_file, $doc_open, $doc_close );

		// Process each typed parameter.
		foreach ( $typed_params as $param ) {
			$param_name   = $param['name'];
			$code_type    = $param['type_hint'];
			$docblock_type_str = $this->code_type_to_docblock( $code_type );

			if ( isset( $existing_params[ $param_name ] ) ) {
				$tag_info = $existing_params[ $param_name ];

				if ( '' === $tag_info['type'] ) {
					// Case A: @param $name exists but has no type.
					$fix = $phpcs_file->addFixableError(
						sprintf( "Missing type for @param %s — code declares '%s'", $param_name, $code_type ),
						$tag_info['string_ptr'],
						'MissingParamType'
					);

					if ( $fix ) {
						$this->fix_empty_param_type( $phpcs_file, $tag_info, $docblock_type_str );
					}
				} else {
					// Both have types — check for drift.
					if ( $this->report_type_drift && ! $this->types_compatible( $code_type, $tag_info['type'] ) ) {
						$phpcs_file->addWarning(
							sprintf(
								"Docblock type '%s' for %s contradicts code type '%s'",
								$tag_info['type'],
								$param_name,
								$code_type
							),
							$tag_info['tag_ptr'],
							'TypeDrift'
						);
					}
				}
			} else {
				// Case B: No @param tag for this typed parameter.
				$fix = $phpcs_file->addFixableError(
					sprintf( "Missing @param tag for typed parameter %s (%s)", $param_name, $code_type ),
					$stack_ptr,
					'MissingParamTag'
				);

				if ( $fix ) {
					$this->fix_missing_param_tag( $phpcs_file, $doc_open, $doc_close, $param_name, $docblock_type_str, $existing_params, $existing_return );
				}
			}
		}

		// Process return type.
		if ( '' !== $return_type && ! $is_no_return ) {
			$docblock_return_str = $this->code_type_to_docblock( $return_type );

			if ( null !== $existing_return ) {
				if ( '' === $existing_return['type'] ) {
					// Case C: @return exists but has no type.
					$fix = $phpcs_file->addFixableError(
						sprintf( "Missing type for @return — code declares '%s'", $return_type ),
						$existing_return['string_ptr'] ?? $existing_return['tag_ptr'],
						'MissingReturnType'
					);

					if ( $fix ) {
						$this->fix_empty_return_type( $phpcs_file, $existing_return, $docblock_return_str );
					}
				} else {
					// Both have types — check for drift.
					if ( $this->report_type_drift && ! $this->types_compatible( $return_type, $existing_return['type'] ) ) {
						$phpcs_file->addWarning(
							sprintf(
								"Docblock return type '%s' contradicts code return type '%s'",
								$existing_return['type'],
								$return_type
							),
							$existing_return['tag_ptr'],
							'TypeDrift'
						);
					}
				}
			} else {
				// Case D: No @return tag.
				$fix = $phpcs_file->addFixableError(
					sprintf( "Missing @return tag — code declares return type '%s'", $return_type ),
					$stack_ptr,
					'MissingReturnTag'
				);

				if ( $fix ) {
					$this->fix_missing_return_tag( $phpcs_file, $doc_open, $doc_close, $docblock_return_str, $existing_params );
				}
			}
		}
	}

	/**
	 * Extracts the return type from code (token scanning after closing paren).
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the function token.
	 *
	 * @return string The return type string, or empty string if none.
	 */
	private function get_return_type_from_code( File $phpcs_file, int $stack_ptr ): string {
		$tokens = $phpcs_file->getTokens();

		if ( ! isset( $tokens[ $stack_ptr ]['parenthesis_closer'] ) ) {
			return '';
		}

		$close_paren = $tokens[ $stack_ptr ]['parenthesis_closer'];

		// Look for a colon after the closing paren.
		$colon_ptr = $phpcs_file->findNext( T_COLON, $close_paren + 1, $close_paren + 3 );

		if ( false === $colon_ptr ) {
			return '';
		}

		// Collect return type tokens until we hit { or ;.
		$type_parts = array();
		$i          = $colon_ptr + 1;

		while ( isset( $tokens[ $i ] ) ) {
			$code = $tokens[ $i ]['code'];

			if ( T_OPEN_CURLY_BRACKET === $code || T_SEMICOLON === $code ) {
				break;
			}

			if ( T_WHITESPACE !== $code ) {
				$type_parts[] = $tokens[ $i ]['content'];
			}

			$i++;
		}

		return implode( '', $type_parts );
	}

	/**
	 * Finds the docblock belonging to this function.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the function token.
	 *
	 * @return int|false The position of the doc comment close tag, or false.
	 */
	private function find_docblock( File $phpcs_file, int $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		$doc_close = $phpcs_file->findPrevious(
			array( T_DOC_COMMENT_CLOSE_TAG ),
			$stack_ptr - 1
		);

		if ( false === $doc_close ) {
			return false;
		}

		// Ensure the docblock belongs to this function.
		for ( $i = $doc_close + 1; $i < $stack_ptr; $i++ ) {
			if ( ! in_array( $tokens[ $i ]['code'], $this->allowed_between, true ) ) {
				return false;
			}
		}

		return $doc_close;
	}

	/**
	 * Checks if the docblock contains {@inheritdoc}.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $doc_open   The position of the doc comment open tag.
	 * @param int  $doc_close  The position of the doc comment close tag.
	 *
	 * @return bool True if docblock contains {@inheritdoc}.
	 */
	private function has_inheritdoc( File $phpcs_file, int $doc_open, int $doc_close ): bool {
		$tokens = $phpcs_file->getTokens();

		for ( $i = $doc_open; $i <= $doc_close; $i++ ) {
			if ( T_DOC_COMMENT_STRING === $tokens[ $i ]['code'] ) {
				if ( preg_match( '/\{@inheritdoc\}/i', $tokens[ $i ]['content'] ) ) {
					return true;
				}
			}
			if ( T_DOC_COMMENT_TAG === $tokens[ $i ]['code'] ) {
				if ( preg_match( '/^\{@inheritdoc\}$/i', $tokens[ $i ]['content'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Parses @param tags from a docblock.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $doc_open   The position of the doc comment open tag.
	 * @param int  $doc_close  The position of the doc comment close tag.
	 *
	 * @return array Keyed by param name, values are arrays with type, tag_ptr, string_ptr.
	 */
	private function parse_param_tags( File $phpcs_file, int $doc_open, int $doc_close ): array {
		$tokens = $phpcs_file->getTokens();
		$params = array();

		for ( $i = $doc_open; $i <= $doc_close; $i++ ) {
			if ( T_DOC_COMMENT_TAG !== $tokens[ $i ]['code'] || '@param' !== $tokens[ $i ]['content'] ) {
				continue;
			}

			$tag_ptr = $i;

			// Find the next DOC_COMMENT_STRING on the same or next tokens.
			$string_ptr = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $i + 1, $doc_close + 1 );

			// Make sure the string is on the same line or next token from the tag
			// (not from a subsequent tag).
			$next_tag = $phpcs_file->findNext( T_DOC_COMMENT_TAG, $i + 1, $doc_close + 1 );
			if ( false !== $next_tag && false !== $string_ptr && $string_ptr > $next_tag ) {
				// The string belongs to a later tag — this @param has no content.
				continue;
			}

			if ( false === $string_ptr ) {
				continue;
			}

			$content = trim( $tokens[ $string_ptr ]['content'] );

			// Find the $variable_name position — handles complex types like
			// array<string, int> or callable(string): int that contain spaces.
			if ( preg_match( '/(\$\w+)/', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
				$param_name  = $matches[1][0];
				$var_pos     = $matches[1][1];
				$param_type  = trim( substr( $content, 0, $var_pos ) );
				$after_var   = trim( substr( $content, $var_pos + strlen( $param_name ) ) );
				$description = $after_var;
			} else {
				continue;
			}

			$params[ $param_name ] = array(
				'type'        => $param_type,
				'tag_ptr'     => $tag_ptr,
				'string_ptr'  => $string_ptr,
				'description' => $description,
			);
		}

		return $params;
	}

	/**
	 * Parses the @return tag from a docblock.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $doc_open   The position of the doc comment open tag.
	 * @param int  $doc_close  The position of the doc comment close tag.
	 *
	 * @return array|null Array with type, tag_ptr, string_ptr, or null if no @return tag.
	 */
	private function parse_return_tag( File $phpcs_file, int $doc_open, int $doc_close ): ?array {
		$tokens = $phpcs_file->getTokens();

		for ( $i = $doc_open; $i <= $doc_close; $i++ ) {
			if ( T_DOC_COMMENT_TAG !== $tokens[ $i ]['code'] || '@return' !== $tokens[ $i ]['content'] ) {
				continue;
			}

			$tag_ptr    = $i;
			$string_ptr = $phpcs_file->findNext( T_DOC_COMMENT_STRING, $i + 1, $doc_close + 1 );

			// Make sure the string is before the next tag.
			$next_tag = $phpcs_file->findNext( T_DOC_COMMENT_TAG, $i + 1, $doc_close + 1 );
			if ( false !== $next_tag && false !== $string_ptr && $string_ptr > $next_tag ) {
				return array(
					'type'       => '',
					'tag_ptr'    => $tag_ptr,
					'string_ptr' => null,
				);
			}

			if ( false === $string_ptr ) {
				return array(
					'type'       => '',
					'tag_ptr'    => $tag_ptr,
					'string_ptr' => null,
				);
			}

			$content     = trim( $tokens[ $string_ptr ]['content'] );
			$type        = $this->extract_type_from_string( $content );
			$description = trim( substr( $content, strlen( $type ) ) );

			return array(
				'type'        => $type,
				'tag_ptr'     => $tag_ptr,
				'string_ptr'  => $string_ptr,
				'description' => $description,
			);
		}

		return null;
	}

	/**
	 * Extracts the type portion from a docblock string, respecting brackets.
	 *
	 * Handles complex types like array<string, int> and callable(string): int
	 * where the type contains spaces.
	 *
	 * @param string $content The docblock string content.
	 *
	 * @return string The type string.
	 */
	private function extract_type_from_string( string $content ): string {
		$len   = strlen( $content );
		$depth = 0;
		$pos   = 0;

		while ( $pos < $len ) {
			$char = $content[ $pos ];

			if ( '<' === $char || '(' === $char || '{' === $char ) {
				$depth++;
			} elseif ( '>' === $char || ')' === $char || '}' === $char ) {
				$depth--;
			} elseif ( ' ' === $char || "\t" === $char ) {
				if ( 0 === $depth ) {
					break;
				}
			}

			$pos++;
		}

		return substr( $content, 0, $pos );
	}

	/**
	 * Converts a code-side type to its PHPDoc equivalent.
	 *
	 * @param string $code_type The PHP code type (e.g., '?string', 'int|string').
	 *
	 * @return string The PHPDoc type (e.g., 'string|null', 'int|string').
	 */
	private function code_type_to_docblock( string $code_type ): string {
		// Handle nullable: ?Type → Type|null.
		if ( 0 === strpos( $code_type, '?' ) ) {
			return substr( $code_type, 1 ) . '|null';
		}

		return $code_type;
	}

	/**
	 * Checks if a docblock type is compatible with a code type.
	 *
	 * A docblock type is compatible if it matches the code type exactly,
	 * or if it's a valid specialization (more specific).
	 *
	 * @param string $code_type    The PHP code type.
	 * @param string $docblock_type The PHPDoc type.
	 *
	 * @return bool True if compatible (same or valid specialization).
	 */
	private function types_compatible( string $code_type, string $docblock_type ): bool {
		$norm_code = $this->normalize_for_comparison( $code_type );
		$norm_doc  = $this->normalize_for_comparison( $docblock_type );

		// Exact match after normalization.
		if ( $norm_code === $norm_doc ) {
			return true;
		}

		// Check if docblock is a valid specialization of the code type.
		return $this->is_specialization( $code_type, $docblock_type );
	}

	/**
	 * Normalizes a type string for comparison.
	 *
	 * Converts ?Type to Type|null format and sorts union parts.
	 *
	 * @param string $type The type string.
	 *
	 * @return string The normalized type string.
	 */
	private function normalize_for_comparison( string $type ): string {
		// Convert ?Type to Type|null.
		if ( 0 === strpos( $type, '?' ) ) {
			$type = substr( $type, 1 ) . '|null';
		}

		// Sort union parts for stable comparison.
		if ( false !== strpos( $type, '|' ) ) {
			$parts = explode( '|', $type );
			$parts = array_map( 'strtolower', $parts );
			sort( $parts );
			return implode( '|', $parts );
		}

		return strtolower( $type );
	}

	/**
	 * Checks if a docblock type is a valid specialization of a code type.
	 *
	 * @param string $code_type    The PHP code type.
	 * @param string $docblock_type The PHPDoc type.
	 *
	 * @return bool True if docblock is a valid specialization.
	 */
	private function is_specialization( string $code_type, string $docblock_type ): bool {
		// Strip nullable wrappers for base comparison.
		$code_nullable = ( 0 === strpos( $code_type, '?' ) );
		$base_code     = $code_nullable ? substr( $code_type, 1 ) : $code_type;

		// Strip |null from docblock type for base comparison.
		$doc_parts       = explode( '|', $docblock_type );
		$doc_has_null    = false;
		$doc_non_null    = array();

		foreach ( $doc_parts as $part ) {
			$trimmed = trim( $part );
			if ( 'null' === strtolower( $trimmed ) ) {
				$doc_has_null = true;
			} else {
				$doc_non_null[] = $trimmed;
			}
		}

		// If code is nullable, docblock should also include null (or we compare bases).
		$base_doc = implode( '|', $doc_non_null );

		// If the base doc type is empty, not compatible.
		if ( '' === $base_doc ) {
			return false;
		}

		$lower_base_code = strtolower( $base_code );

		// array → string[], Type[], array<...>.
		if ( 'array' === $lower_base_code ) {
			if ( preg_match( '/\[\]$/', $base_doc ) ) {
				return true;
			}
			if ( preg_match( '/^array\s*</', strtolower( $base_doc ) ) ) {
				return true;
			}
		}

		// iterable → iterable<...>.
		if ( 'iterable' === $lower_base_code ) {
			if ( preg_match( '/^iterable\s*</', strtolower( $base_doc ) ) ) {
				return true;
			}
		}

		// object → specific class name.
		if ( 'object' === $lower_base_code ) {
			// If the docblock type looks like a class name (not a primitive).
			if ( $this->looks_like_class_name( $base_doc ) ) {
				return true;
			}
		}

		// callable → \Closure or callable(...).
		if ( 'callable' === $lower_base_code ) {
			if ( 'closure' === strtolower( $base_doc ) || '\\closure' === strtolower( $base_doc ) ) {
				return true;
			}
			if ( preg_match( '/^callable\s*\(/', strtolower( $base_doc ) ) ) {
				return true;
			}
			if ( preg_match( '/^\\\\?Closure$/', $base_doc ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a type string looks like a class name.
	 *
	 * @param string $type The type string.
	 *
	 * @return bool True if it looks like a class name.
	 */
	private function looks_like_class_name( string $type ): bool {
		$builtin_types = array(
			'string', 'int', 'float', 'bool',
			'array', 'callable', 'iterable', 'object',
			'void', 'mixed', 'never', 'null',
			'self', 'parent', 'static', 'false', 'true',
		);

		$lower = strtolower( $type );

		if ( in_array( $lower, $builtin_types, true ) ) {
			return false;
		}

		// Check array notations.
		if ( preg_match( '/\[\]$/', $type ) || preg_match( '/</', $type ) ) {
			return false;
		}

		return (bool) preg_match( '/^\\\\?[a-zA-Z_][a-zA-Z0-9_\\\\]*$/', $type );
	}

	/**
	 * Handles the case where no docblock exists at all.
	 *
	 * @param File  $phpcs_file  The file being scanned.
	 * @param int   $stack_ptr   The position of the function token.
	 * @param array $typed_params The typed parameters.
	 * @param string $return_type The return type.
	 * @param bool  $is_no_return Whether this is a method that shouldn't have @return.
	 *
	 * @return void
	 */
	private function handle_missing_docblock( File $phpcs_file, int $stack_ptr, array $typed_params, string $return_type, bool $is_no_return ): void {
		$fix = $phpcs_file->addFixableError(
			'Missing docblock for function with typed parameters or return type',
			$stack_ptr,
			'MissingDocblock'
		);

		if ( ! $fix ) {
			return;
		}

		$tokens = $phpcs_file->getTokens();

		// Find the first token of the function declaration (modifiers or function keyword).
		$insert_before = $stack_ptr;
		$check         = $stack_ptr - 1;

		while ( $check >= 0 ) {
			$code = $tokens[ $check ]['code'];
			if ( in_array( $code, array( T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL ), true ) ) {
				$insert_before = $check;
				$check--;
			} elseif ( T_WHITESPACE === $code ) {
				$check--;
			} else {
				break;
			}
		}

		// Determine indentation by looking at the whitespace token preceding
		// the insertion point. Extract everything after the last newline.
		$indent = '';
		if ( $insert_before > 0 && T_WHITESPACE === $tokens[ $insert_before - 1 ]['code'] ) {
			$ws           = $tokens[ $insert_before - 1 ]['content'];
			$last_newline = strrpos( $ws, "\n" );
			if ( false !== $last_newline ) {
				$indent = substr( $ws, $last_newline + 1 );
			} else {
				$indent = $ws;
			}
		}

		// Build the docblock. First line has no indent because addContentBefore
		// inserts after the preceding whitespace token which already provides it.
		$docblock = "/**\n";
		$docblock .= $indent . " * [Description placeholder.]\n";
		$docblock .= $indent . " *\n";

		// Calculate max type length for alignment.
		$max_type_len = 0;
		foreach ( $typed_params as $param ) {
			$doc_type = $this->code_type_to_docblock( $param['type_hint'] );
			$len      = strlen( $doc_type );
			if ( $len > $max_type_len ) {
				$max_type_len = $len;
			}
		}

		// Add @param tags.
		$has_params = false;
		foreach ( $typed_params as $param ) {
			$doc_type   = $this->code_type_to_docblock( $param['type_hint'] );
			$padded     = str_pad( $doc_type, $max_type_len );
			$param_name = $param['name'];

			$docblock .= $indent . " * @param " . $padded . " " . $param_name . " [Description.]\n";
			$has_params = true;
		}

		// Add @return tag.
		if ( '' !== $return_type && ! $is_no_return ) {
			if ( $has_params ) {
				$docblock .= $indent . " *\n";
			}
			$docblock .= $indent . " * @return " . $this->code_type_to_docblock( $return_type ) . "\n";
		}

		$docblock .= $indent . " */\n" . $indent;

		$phpcs_file->fixer->addContentBefore( $insert_before, $docblock );
	}

	/**
	 * Fixes an empty type on an existing @param tag.
	 *
	 * Replaces "$name desc" with "type $name desc" on the DOC_COMMENT_STRING token.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param array  $tag_info   The tag info from parse_param_tags.
	 * @param string $type       The type to insert.
	 *
	 * @return void
	 */
	private function fix_empty_param_type( File $phpcs_file, array $tag_info, string $type ): void {
		$tokens  = $phpcs_file->getTokens();
		$ptr     = $tag_info['string_ptr'];
		$content = $tokens[ $ptr ]['content'];

		// Prepend the type before the existing content.
		$phpcs_file->fixer->replaceToken( $ptr, $type . ' ' . $content );
	}

	/**
	 * Inserts a new @param tag line in the docblock.
	 *
	 * @param File   $phpcs_file      The file being scanned.
	 * @param int    $doc_open        The position of the doc comment open tag.
	 * @param int    $doc_close       The position of the doc comment close tag.
	 * @param string $param_name      The parameter name (including $).
	 * @param string $type            The docblock type.
	 * @param array  $existing_params The existing param tags.
	 * @param array|null $existing_return The existing return tag info.
	 *
	 * @return void
	 */
	private function fix_missing_param_tag( File $phpcs_file, int $doc_open, int $doc_close, string $param_name, string $type, array $existing_params, ?array $existing_return ): void {
		$tokens = $phpcs_file->getTokens();

		// Determine indent from the docblock.
		$indent = $this->get_docblock_indent( $phpcs_file, $doc_open );

		$new_line = $indent . " * @param " . $type . " " . $param_name . " [Description.]\n";

		// Find insertion point: after last @param, or before @return, or before */.
		$insert_after = null;

		// Find the last @param tag's line end.
		$last_param_line_end = null;
		foreach ( $existing_params as $info ) {
			$tag_line = $tokens[ $info['tag_ptr'] ]['line'];
			// Find the end of this tag's line.
			for ( $j = $info['tag_ptr']; $j <= $doc_close; $j++ ) {
				if ( $tokens[ $j ]['line'] > $tag_line ) {
					$last_param_line_end = $j - 1;
					break;
				}
			}
			if ( null === $last_param_line_end ) {
				$last_param_line_end = $doc_close - 1;
			}
		}

		if ( null !== $last_param_line_end ) {
			$phpcs_file->fixer->addContent( $last_param_line_end, $new_line );
			return;
		}

		// No existing @param tags. Insert before @return if it exists.
		if ( null !== $existing_return ) {
			// Find the line start of the @return tag.
			$return_line = $tokens[ $existing_return['tag_ptr'] ]['line'];
			for ( $j = $existing_return['tag_ptr'] - 1; $j >= $doc_open; $j-- ) {
				if ( $tokens[ $j ]['line'] < $return_line ) {
					$phpcs_file->fixer->addContent( $j, $new_line );
					return;
				}
			}
		}

		// No @param or @return tags. Insert before */.
		$this->insert_before_close( $phpcs_file, $doc_close, $new_line );
	}

	/**
	 * Fixes an empty @return type.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param array  $tag_info   The return tag info from parse_return_tag.
	 * @param string $type       The type to insert.
	 *
	 * @return void
	 */
	private function fix_empty_return_type( File $phpcs_file, array $tag_info, string $type ): void {
		if ( null !== $tag_info['string_ptr'] ) {
			$tokens  = $phpcs_file->getTokens();
			$content = $tokens[ $tag_info['string_ptr'] ]['content'];
			$phpcs_file->fixer->replaceToken( $tag_info['string_ptr'], $type . ' ' . $content );
		} else {
			// @return tag exists but has no string content — add type after the tag.
			$phpcs_file->fixer->addContent( $tag_info['tag_ptr'], ' ' . $type );
		}
	}

	/**
	 * Inserts a new @return tag line in the docblock.
	 *
	 * @param File   $phpcs_file      The file being scanned.
	 * @param int    $doc_open        The position of the doc comment open tag.
	 * @param int    $doc_close       The position of the doc comment close tag.
	 * @param string $type            The docblock return type.
	 * @param array  $existing_params The existing param tags.
	 *
	 * @return void
	 */
	private function fix_missing_return_tag( File $phpcs_file, int $doc_open, int $doc_close, string $type, array $existing_params ): void {
		$tokens = $phpcs_file->getTokens();
		$indent = $this->get_docblock_indent( $phpcs_file, $doc_open );

		// Add blank line separator if there are @param tags.
		$separator = '';
		if ( ! empty( $existing_params ) ) {
			$separator = $indent . " *\n";
		}

		$new_line = $separator . $indent . " * @return " . $type . "\n";

		// Find insertion point: after last @param, or before */.
		$last_param_line_end = null;
		foreach ( $existing_params as $info ) {
			$tag_line = $tokens[ $info['tag_ptr'] ]['line'];
			for ( $j = $info['tag_ptr']; $j <= $doc_close; $j++ ) {
				if ( $tokens[ $j ]['line'] > $tag_line ) {
					$candidate = $j - 1;
					if ( null === $last_param_line_end || $candidate > $last_param_line_end ) {
						$last_param_line_end = $candidate;
					}
					break;
				}
			}
		}

		if ( null !== $last_param_line_end ) {
			$phpcs_file->fixer->addContent( $last_param_line_end, $new_line );
			return;
		}

		// No @param tags — insert before */.
		$this->insert_before_close( $phpcs_file, $doc_close, $new_line );
	}

	/**
	 * Inserts content before the closing tag of a docblock.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param int    $doc_close  The position of the doc comment close tag.
	 * @param string $content    The content to insert.
	 *
	 * @return void
	 */
	private function insert_before_close( File $phpcs_file, int $doc_close, string $content ): void {
		$tokens = $phpcs_file->getTokens();

		// Find the token just before */ on its line.
		$close_line = $tokens[ $doc_close ]['line'];
		for ( $j = $doc_close - 1; $j >= 0; $j-- ) {
			if ( $tokens[ $j ]['line'] < $close_line ) {
				$phpcs_file->fixer->addContent( $j, $content );
				return;
			}
		}

		// Fallback: add before the close tag.
		$phpcs_file->fixer->addContentBefore( $doc_close, $content );
	}

	/**
	 * Gets the indentation of a docblock.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $doc_open   The position of the doc comment open tag.
	 *
	 * @return string The indentation string.
	 */
	private function get_docblock_indent( File $phpcs_file, int $doc_open ): string {
		$tokens = $phpcs_file->getTokens();
		$column = $tokens[ $doc_open ]['column'];

		if ( $column <= 1 ) {
			return '';
		}

		// Check for a whitespace token before the docblock open on the same line.
		if ( $doc_open > 0 && T_WHITESPACE === $tokens[ $doc_open - 1 ]['code'] && $tokens[ $doc_open - 1 ]['line'] === $tokens[ $doc_open ]['line'] ) {
			return $tokens[ $doc_open - 1 ]['content'];
		}

		return str_repeat( "\t", (int) floor( ( $column - 1 ) / 4 ) );
	}
}
