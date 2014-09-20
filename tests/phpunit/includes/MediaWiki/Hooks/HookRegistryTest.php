<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\HookRegistry;

/**
 * @covers \SMW\MediaWiki\Hooks\HookRegistry
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HookRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\HookRegistry',
			new HookRegistry()
		);
	}

	public function testInvalidHookDefinitionRequestThrowsException() {

		$instance = new HookRegistry();

		$this->setExpectedException( 'RuntimeException' );
		$instance->getDefinition( 'foo' );
	}

	public function testFunctionHookDefinition() {

		$instance = new HookRegistry();

		$this->assertThatDefinitionIsClosure(
			$instance,
			$instance->getListOfRegisteredFunctionHooks()
		);
	}

	public function testParserFunctionDefinition() {

		$instance = new HookRegistry();

		$this->assertThatDefinitionIsClosure(
			$instance,
			$instance->getListOfRegisteredParserFunctions()
		);
	}

	private function assertThatDefinitionIsClosure( HookRegistry $instance, $listOfItems ) {

		foreach ( $listOfItems as $name ) {
			$this->assertInstanceOf(
				'\Closure',
				$instance->getDefinition( $name )
			);
		}
	}

}
