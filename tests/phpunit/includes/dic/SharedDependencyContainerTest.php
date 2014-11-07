<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\SimpleDependencyBuilder;

use SMW\DependencyBuilder;
use SMW\DependencyContainer;

/**
 * @covers \SMW\SharedDependencyContainer
 * @covers \SMW\SimpleDependencyBuilder
 * @covers \SMW\BaseDependencyContainer
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SharedDependencyContainerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SimpleDependencyBuilder';
	}

	/**
	 * @since 1.9
	 *
	 * @return SimpleDependencyBuilder
	 */
	private function newInstance( $container = null ) {
		return new SimpleDependencyBuilder( $container );
	}

	/**
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

			$newInstance = $instance->newObject( $objectName );

			if ( $newInstance !== null ) {

				$this->assertInstanceOf(
					$objectInstance,
					$newInstance,
					'Asserts that newObject() was able to create an object instance'
				);

			}

			$this->assertTrue( true );
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

		foreach ( $instance->loadAllDefinitions() as $objectName => $objectSiganture ) {
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

		$provider[] = array( 'RequestContext',             array( '\IContextSource'                  => array() ) );
		$provider[] = array( 'TitleCreator',               array( '\SMW\Mediawiki\TitleCreator'      => array() ) );
		$provider[] = array( 'PageCreator',                array( '\SMW\Mediawiki\PageCreator'       => array() ) );
		$provider[] = array( 'JobFactory',                 array( '\SMW\Mediawiki\Jobs\JobFactory'   => array() ) );

		$provider[] = array( 'RequestContext', array( '\IContextSource' => array(
				'Title'    => $this->newMockBuilder()->newObject( 'Title' ),
				'Language' => $this->newMockBuilder()->newObject( 'Language' )
				)
			)
		);

		$provider[] = array( 'WikiPage', array( '\WikiPage' => array(
				'Title' => $this->newMockBuilder()->newObject( 'Title' )
				)
			)
		);

		$provider[] = array( 'ContentParser', array( '\SMW\ContentParser' => array(
				'Title'        => $this->newMockBuilder()->newObject( 'Title' )
				)
			)
		);

		$provider[] = array( 'ParserData', array( '\SMW\ParserData' => array(
				'Title'        => $this->newMockBuilder()->newObject( 'Title' ),
				'ParserOutput' => $this->newMockBuilder()->newObject( 'ParserOutput' )
				)
			)
		);

		$provider[] = array( 'MessageFormatter', array( '\SMW\MessageFormatter' => array(
				'Language' => $this->newMockBuilder()->newObject( 'Language' )
				)
			)
		);

		return $provider;
	}

}
