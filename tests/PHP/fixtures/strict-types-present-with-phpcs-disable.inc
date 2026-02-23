<?php
/**
 * Test file: strict_types present with phpcs:disable in docblock.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 */

declare(strict_types=1);

class TestClass {
	/**
	 * A method that does things.
	 */
	public function do_stuff( array $args, array $assoc_args ): void {
		echo 'test';
	}
}
