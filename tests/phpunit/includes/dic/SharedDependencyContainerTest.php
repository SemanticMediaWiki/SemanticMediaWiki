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
 * @covers \SMW\DiParserData
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
	private function newInstance( $container = null ) {
		return new SimpleDependencyBuilder( $container );
	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @test SimpleDependencyBuilder::addArgument
	 * @dataProvider objectDataProvider
	 *
	 * @since 1.9
	 */
	public function testObjectRegistrationAndInstanitation( $objectName, $objectDefinition ) {

		$instance = $this->newInstance( new SharedDependencyContainer() );

		foreach ( $objectDefinition as $objectInstance => $arguments ) {

			foreach ( $arguments as $name => $object ) {
				$instance->addArgument( $name, $object );
			}

			$this->assertInstanceOf(
				$objectInstance,
				$instance->newObject( $objectName ),
				'asserts that the DiObject was able to create an instance'
			);
		}

	}

	/**
	 * @since 1.9
	 */
	public function testObjectRegistrationCompleteness() {

		$instance = new SharedDependencyContainer();

		foreach ( $this->objectDataProvider() as $object ) {
			$registeredObjects[ $object[0] ] = array() ;
		}

		foreach ( $instance->toArray() as $objectName => $objectSiganture ) {
			$this->assertObjectRegistration( $objectName, $registeredObjects );
		}

		foreach ( $instance->loadObjects() as $objectName => $objectSiganture ) {
			$this->assertObjectRegistration( $objectName, $registeredObjects );
		}

		$this->assertTrue( true );
	}

	/**
	 * Asserts whether a registered object is being tested
	 */
	public function assertObjectRegistration( $name, $objects ) {
		if ( !array_key_exists( $name, $objects ) ) {
			$this->markTestIncomplete( "This test is incomplete because of a missing {$name} assertion." );
		}
	}

	/**
	 * @return array
	 */
	public function objectDataProvider() {

		$provider = array();

		$provider[] = array( 'Settings',                   array( '\SMW\Settings'                    => array() ) );
		$provider[] = array( 'Store',                      array( '\SMW\Store'                       => array() ) );
		$provider[] = array( 'CacheHandler',               array( '\SMW\CacheHandler'                => array() ) );
		$provider[] = array( 'NamespaceExaminer',          array( '\SMW\NamespaceExaminer'           => array() ) );
		$provider[] = array( 'UpdateObserver',             array( '\SMW\UpdateObserver'              => array() ) );
		$provider[] = array( 'ObservableUpdateDispatcher', array( '\SMW\ObservableSubjectDispatcher' => array() ) );

		$provider[] = array( 'ContentParser', array( '\SMW\ContentParser' => array(
				'Title'        => $this->newMockBuilder()->newObject( 'Title' )
				)
			)
		);

		$provider[] = array( 'ContentProcessor', array( '\SMW\ParserTextProcessor' => array(
				'ParserData'  => $this->newMockBuilder()->newObject( 'ParserData' )
				)
			)
		);

		$provider[] = array( 'Factbox', array( '\SMW\Factbox' => array(
				'ParserData'     => $this->newMockBuilder()->newObject( 'ParserData' ),
				'RequestContext' => $this->newMockBuilder()->newObject( 'RequestContext' )
				)
			)
		);

		$provider[] = array( 'FactboxPresenter', array( '\SMW\FactboxPresenter' => array(
				'OutputPage'  => $this->newMockBuilder()->newObject( 'OutputPage' )
				)
			)
		);

		$provider[] = array( 'BasePropertyAnnotator', array( '\SMW\BasePropertyAnnotator' => array(
				'SemanticData' => $this->newMockBuilder()->newObject( 'SemanticData' )
				)
			)
		);

		$provider[] = array( 'ParserData', array( '\SMW\ParserData' => array(
				'Title'        => $this->newMockBuilder()->newObject( 'Title' ),
				'ParserOutput' => $this->newMockBuilder()->newObject( 'ParserOutput' )
				)
			)
		);

		return $provider;
	}
}
