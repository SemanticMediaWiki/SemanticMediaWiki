<?php

namespace SMW\Test;

use SMW\FunctionHookRegistry;
use SMW\EmptyContext;

/**
 * @covers \SMW\FunctionHookRegistry
 * @covers \SMW\FunctionHook
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FunctionHookRegistryTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FunctionHookRegistry';
	}

	/**
	 * @since 1.9
	 *
	 * @return FunctionHook
	 */
	private function newHook() {
		return $this->getMockForAbstractClass( '\SMW\FunctionHook' );
	}

	/**
	 * @since 1.9
	 *
	 * @return FunctionHookRegistry
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
			$this->newInstance()->register( $this->newHook() ),
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
			$this->newInstance( new EmptyContext() )->withContext(),
			'Asserts that getContext() returns a empty context'
		);

	}

}
