<?php

namespace SMW\Test;

use SMW\CommonDependencyContainer;
use SMW\SimpleDependencyBuilder;

use SMW\DependencyBuilder;
use SMW\DependencyContainer;

/**
 * Tests for the CommonDependencyContainer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\CommonDependencyContainer
 * @covers \SMW\SimpleDependencyBuilder
 * @covers \SMW\DependencyContainerBase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class CommonDependencyContainerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SimpleDependencyBuilder';
	}

	/**
	 * Helper method that returns a SimpleDependencyBuilder object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return SimpleDependencyBuilder
	 */
	private function getInstance( $container = null ) {
		return new SimpleDependencyBuilder( $container );
	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @test SimpleDependencyBuilder::addArgument
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testCommonDependencyContainer() {

		$instance = $this->getInstance( new CommonDependencyContainer() );

		$this->assertInstanceOf( '\SMW\Settings', $instance->newObject( 'Settings' ) );
		$this->assertInstanceOf( '\SMW\Store', $instance->newObject( 'Store' ) );
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->newObject( 'CacheHandler' ) );

		$instance->addArgument( 'Title', $this->newMockObject()->getMockTitle() );
		$instance->addArgument( 'ParserOutput', $this->newMockObject()->getMockParserOutput() );

		$this->assertInstanceOf( '\SMW\ParserData', $instance->newObject( 'ParserData' ) );

	}
}
