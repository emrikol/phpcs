<?php
/**
 * Test file: missing strict_types with phpcs:disable in docblock.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 */

class TestClass {
	/**
	 * A method that does things.
	 */
	public function do_stuff( array $args, array $assoc_args ): void {
		echo 'test';
	}
}
