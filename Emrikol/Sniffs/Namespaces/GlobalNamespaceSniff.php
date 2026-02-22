<?php
/**
 * Emrikol Coding Standards.
 *
 * Ensures global classes in namespaced code are properly qualified with a
 * leading backslash or imported via a use statement.
 *
 * Supports three ways to define which classes to check:
 * 1. $known_global_classes — explicit class list (configurable via ruleset.xml)
 * 2. $class_patterns       — regex patterns (configurable via ruleset.xml)
 * 3. Preset XML files      — drop-in lists for WordPress, core PHP, etc.
 *
 * @package Emrikol\Sniffs\Namespaces
 */

namespace Emrikol\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

require_once dirname( __DIR__, 2 ) . '/Utils/PhpBuiltinClasses.php';

/**
 * Class GlobalNamespaceSniff
 *
 * Checks that global classes used in namespaced contexts are properly
 * referenced with a leading backslash or imported with a use statement.
 * Supports auto-fixing via phpcbf by prepending a backslash.
 */
class GlobalNamespaceSniff implements Sniff {

	/**
	 * Explicit list of global class names to check.
	 *
	 * Configurable via ruleset.xml property or preset files.
	 *
	 * @var array
	 */
	public $known_global_classes = array();

	/**
	 * Whether to auto-detect PHP built-in classes at runtime.
	 *
	 * When true (default), uses get_declared_classes() and
	 * get_declared_interfaces() filtered by ReflectionClass::isInternal()
	 * to automatically recognize built-in PHP classes without needing
	 * a preset file.
	 *
	 * Configurable via ruleset.xml. Set to false to disable and rely
	 * solely on explicit lists, patterns, and presets.
	 *
	 * @var bool
	 */
	public $auto_detect_php_classes = true;

	/**
	 * Regex patterns to match global class names.
	 *
	 * Each pattern is tested against unqualified class names found in code.
	 * Example: '/^WP_/' to match all WordPress classes.
	 *
	 * @var array
	 */
	public $class_patterns = array();

	/**
	 * Collected use statements for the current file.
	 *
	 * @var array
	 */
	private $use_statements = array();

	/**
	 * Tracks errors already flagged to avoid duplicates.
	 *
	 * @var array
	 */
	private $flagged_errors = array();

	/**
	 * The file currently being processed (used to reset state between files).
	 *
	 * @var string
	 */
	private $current_file = '';

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register(): array {
		return array( T_NAMESPACE, T_USE, T_FUNCTION, T_STRING );
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
		// Reset state when processing a new file.
		$filename = $phpcs_file->getFilename();
		if ( $this->current_file !== $filename ) {
			$this->current_file  = $filename;
			$this->use_statements = array();
			$this->flagged_errors = array();
		}

		if ( ! $this->file_has_namespace( $phpcs_file ) ) {
			return;
		}

		$tokens = $phpcs_file->getTokens();

		switch ( $tokens[ $stack_ptr ]['code'] ) {
			case T_USE:
				$this->process_use_statement( $phpcs_file, $stack_ptr );
				break;
			case T_FUNCTION:
				$this->process_function( $phpcs_file, $stack_ptr );
				break;
			case T_STRING:
				$this->process_string( $phpcs_file, $stack_ptr );
				break;
		}
	}

	/**
	 * Checks if the file has a namespace declaration.
	 *
	 * @param File $phpcs_file The file being scanned.
	 *
	 * @return bool True if the file has a namespace, false otherwise.
	 */
	private function file_has_namespace( File $phpcs_file ): bool {
		$tokens = $phpcs_file->getTokens();
		foreach ( $tokens as $token ) {
			if ( T_NAMESPACE === $token['code'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tracks a use statement for later checking.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the T_USE token.
	 *
	 * @return void
	 */
	private function process_use_statement( File $phpcs_file, int $stack_ptr ): void {
		$tokens        = $phpcs_file->getTokens();
		$end_ptr       = $phpcs_file->findNext( T_SEMICOLON, $stack_ptr );
		$use_statement = $phpcs_file->getTokensAsString( $stack_ptr + 1, $end_ptr - $stack_ptr - 1 );

		$this->use_statements[] = trim( $use_statement );
	}

	/**
	 * Checks function parameter type hints and return types for unqualified global classes.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the T_FUNCTION token.
	 *
	 * @return void
	 */
	private function process_function( File $phpcs_file, int $stack_ptr ): void {
		$tokens           = $phpcs_file->getTokens();
		$function_end_ptr = $tokens[ $stack_ptr ]['parenthesis_closer'];
		$colon_ptr        = $phpcs_file->findNext( T_COLON, $function_end_ptr, $function_end_ptr + 2 );

		// Check parameter type hints.
		$param_ptr = $phpcs_file->findNext( T_VARIABLE, $stack_ptr, $function_end_ptr );
		while ( false !== $param_ptr && $param_ptr < $function_end_ptr ) {
			$type_hint_ptr = $phpcs_file->findPrevious( array( T_STRING, T_NS_SEPARATOR ), $param_ptr - 1, $stack_ptr );
			if ( false !== $type_hint_ptr && T_STRING === $tokens[ $type_hint_ptr ]['code'] ) {
				$type_hint   = '';
				$current_ptr = $type_hint_ptr;
				$first_ptr   = $type_hint_ptr;
				while ( T_STRING === $tokens[ $current_ptr ]['code'] || T_NS_SEPARATOR === $tokens[ $current_ptr ]['code'] ) {
					$type_hint = $tokens[ $current_ptr ]['content'] . $type_hint;
					$first_ptr = $current_ptr;
					--$current_ptr;
				}
				if ( $this->is_global_class( $type_hint ) && ! $this->is_imported( $type_hint ) && ! $this->is_already_prefixed( $first_ptr, $tokens ) ) {
					$this->add_fixable_error(
						$phpcs_file,
						"Global class '{$type_hint}' used as type hint should be referenced with a leading backslash or imported with a 'use' statement.",
						$first_ptr,
						'GlobalNamespaceTypeHint'
					);
				}
			}
			$param_ptr = $phpcs_file->findNext( T_VARIABLE, $param_ptr + 1, $function_end_ptr );
		}

		// Check return type.
		if ( false !== $colon_ptr ) {
			$return_type_ptr = $phpcs_file->findNext( array( T_STRING, T_NS_SEPARATOR ), $colon_ptr + 1, null, false, null, true );

			if ( false !== $return_type_ptr ) {
				$return_type     = '';
				$first_ptr       = $return_type_ptr;
				$current_ptr     = $return_type_ptr;
				while ( T_STRING === $tokens[ $current_ptr ]['code'] || T_NS_SEPARATOR === $tokens[ $current_ptr ]['code'] ) {
					$return_type .= $tokens[ $current_ptr ]['content'];
					++$current_ptr;
				}

				$return_types = explode( '|', $return_type );
				foreach ( $return_types as $type ) {
					if ( $this->is_global_class( $type ) && ! $this->is_imported( $type ) && ! $this->is_already_prefixed( $first_ptr, $tokens ) ) {
						$this->add_fixable_error(
							$phpcs_file,
							"Global class '{$type}' used as return type should be referenced with a leading backslash or imported with a 'use' statement.",
							$colon_ptr,
							'GlobalNamespaceReturnType'
						);
					}
				}
			}
		}
	}

	/**
	 * Checks string tokens for unqualified global class references.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the T_STRING token.
	 *
	 * @return void
	 */
	private function process_string( File $phpcs_file, int $stack_ptr ): void {
		$tokens  = $phpcs_file->getTokens();
		$content = $tokens[ $stack_ptr ]['content'];

		if ( ! $this->is_global_class( $content ) || $this->is_imported( $content ) ) {
			return;
		}

		$prev_ptr = $phpcs_file->findPrevious( T_WHITESPACE, $stack_ptr - 1, null, true );
		if ( T_NS_SEPARATOR === $tokens[ $prev_ptr ]['code'] ) {
			return;
		}

		$line = $tokens[ $stack_ptr ]['line'];
		if ( isset( $this->flagged_errors[ $line ]['GlobalNamespace'] ) ) {
			return;
		}

		$this->add_fixable_error(
			$phpcs_file,
			"Global class '{$content}' should be referenced with a leading backslash or imported with a 'use' statement.",
			$stack_ptr,
			'GlobalNamespace'
		);
	}

	/**
	 * Checks if a class name matches the configured global classes or patterns.
	 *
	 * @param string $class_name The class name to check.
	 *
	 * @return bool True if it's a known global class, false otherwise.
	 */
	private function is_global_class( string $class_name ): bool {
		// Check explicit class list.
		if ( in_array( $class_name, $this->known_global_classes, true ) ) {
			return true;
		}

		// Check regex patterns.
		foreach ( $this->class_patterns as $pattern ) {
			if ( preg_match( $pattern, $class_name ) ) {
				return true;
			}
		}

		// Check auto-detected PHP built-in classes.
		if ( $this->auto_detect_php_classes && in_array( $class_name, \Emrikol\Utils\PhpBuiltinClasses::get_classes(), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a class name has been imported via a use statement.
	 *
	 * @param string $class_name The class name to check.
	 *
	 * @return bool True if the class is imported, false otherwise.
	 */
	private function is_imported( string $class_name ): bool {
		foreach ( $this->use_statements as $use_statement ) {
			if ( preg_match( '/\b' . preg_quote( $class_name, '/' ) . '\b/', $use_statement ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the class reference is already prefixed with a namespace separator.
	 *
	 * @param int   $ptr    The pointer to the class token.
	 * @param array $tokens The token array.
	 *
	 * @return bool True if already prefixed, false otherwise.
	 */
	private function is_already_prefixed( int $ptr, array $tokens ): bool {
		return isset( $tokens[ $ptr - 1 ] ) && T_NS_SEPARATOR === $tokens[ $ptr - 1 ]['code'];
	}

	/**
	 * Adds a fixable error and applies the backslash prefix fix if enabled.
	 *
	 * @param File   $phpcs_file The file being scanned.
	 * @param string $error      The error message.
	 * @param int    $ptr        The position of the error in the file.
	 * @param string $code       The error code.
	 *
	 * @return void
	 */
	private function add_fixable_error( File $phpcs_file, string $error, int $ptr, string $code ): void {
		$line = $phpcs_file->getTokens()[ $ptr ]['line'];

		if ( isset( $this->flagged_errors[ $line ][ $code ] ) ) {
			return;
		}

		$this->flagged_errors[ $line ][ $code ] = true;

		$fix = $phpcs_file->addFixableError( $error, $ptr, $code );

		if ( $fix ) {
			$phpcs_file->fixer->addContentBefore( $ptr, '\\' );
		}
	}
}
