<?php

namespace SMW\Test;

use SMW\FunctionHookRegistry;

/**
 * Tests for the FunctionHookRegistry class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\FunctionHookRegistry
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class FunctionHookRegistryTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FunctionHookRegistry';
	}

	/**
	 * Helper method that returns a FunctionHook object
	 *
	 * @since 1.9
	 */
	private function newHook() {
		return $this->getMockForAbstractClass( '\SMW\FunctionHook' );
	}

	/**
	 * Helper method that returns a FunctionHookRegistry object
	 *
	 * @since 1.9
	 */
	private function newInstance( $context = null ) {
		return new FunctionHookRegistry( $context );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {

		$this->assertInstanceOf(
			'\SMW\FunctionHook',
			FunctionHookRegistry::register( $this->newHook() ),
			'Asserts that register() returns a FunctionHook instance'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testWithContext() {

		$this->assertInstanceOf(
			'\SMW\ContextResource',
			$this->newInstance()->withContext(),
			'Asserts that getContext() returns a default context'
		);

		$this->assertInstanceOf(
			'\SMW\EmptyContext',
			$this->newInstance( new \SMW\EmptyContext() )->withContext(),
			'Asserts that getContext() returns a empty context'
		);

	}

}
