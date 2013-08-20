<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\SimpleDependencyBuilder;

use SMW\DependencyBuilder;
use SMW\DependencyContainer;

/**
 * Tests for the SharedDependencyContainer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SharedDependencyContainer
 * @covers \SMW\SimpleDependencyBuilder
 * @covers \SMW\BaseDependencyContainer
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SharedDependencyContainerTest extends SemanticMediaWikiTestCase {

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
	public function testSharedDependencyContainer() {

		$instance = $this->getInstance( new SharedDependencyContainer() );

		$this->assertInstanceOf( '\SMW\Settings', $instance->newObject( 'Settings' ) );
		$this->assertInstanceOf( '\SMW\Store', $instance->newObject( 'Store' ) );
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->newObject( 'CacheHandler' ) );

		$instance->addArgument( 'Title', $this->newMockObject()->getMockTitle() );
		$instance->addArgument( 'ParserOutput', $this->newMockObject()->getMockParserOutput() );

		$this->assertInstanceOf( '\SMW\ParserData', $instance->newObject( 'ParserData' ) );

	}
}
