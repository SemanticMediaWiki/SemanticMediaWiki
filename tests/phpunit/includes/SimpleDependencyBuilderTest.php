<?php

namespace SMW\Test;

use SMW\EmptyDependencyContainer;
use SMW\SimpleDependencyBuilder;
use SMW\DependencyBuilder;

use SMW\DIWikiPage;

/**
 * Tests for the Observer/Subject pattern
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SimpleDependencyBuilder
 * @covers \SMW\DependencyInjector
 * @covers \SMW\DependencyContainerBase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SimpleDependencyBuilderTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SimpleDependencyBuilder';
	}

	/**
	 * Helper method that returns a DependencyContainer object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return DependencyContainer
	 */
	private function newDependencyContainer( $expected = array() ) {

		$container = $this->getMockBuilder( '\SMW\DependencyContainer' )
			->disableOriginalConstructor()
			->setMethods( array( 'preload', 'toArray', 'registerObject', 'has', 'get', 'set', 'remove', 'merge' ) )
			->getMock();

		$container->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( $expected ) );

		return $container;
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
	private function getInstance( $dependencyContainer = null ) {
		return new SimpleDependencyBuilder( $dependencyContainer );
	}

	/**
	 * @test SimpleDependencyBuilder::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterContainer() {

		$instance = $this->getInstance();

		// Register container
		$container = $this->newDependencyContainer( array( 'Test' => '123' ) );
		$instance->registerContainer( $container );

		$this->assertEquals( '123', $instance->newObject( 'Test' ) );

		// Register additional container
		$container = $this->newDependencyContainer( array( 'Test2' => 9001 ) );
		$instance->registerContainer( $container );

		$this->assertEquals( '123', $instance->newObject( 'Test' ) );
		$this->assertEquals( 9001, $instance->newObject( 'Test2' ) );

	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObjectMagicMethod() {

		$instance = $this->getInstance();

		// Register objects
		$container = new EmptyDependencyContainer();

		// Eager loading
		$container->someFunnyTitle = $this->newTitle();

		// Lazy loading
		$container->diwikipage = function ( DependencyBuilder $builder ) {
			return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		};

		// Register container
		$instance->registerContainer( $container );
		$this->assertInstanceOf( 'Title', $instance->someFunnyTitle );

		// Adds necessary argument object needed for the DIWikiPage build process
		$instance->addArgument( 'Title', $instance->someFunnyTitle );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->diwikipage );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->newObject( 'diwikipage' ) );

		$this->assertTrue( $instance->diwikipage !== $instance->newObject( 'diwikipage' ) );

		$instance->registerContainer(  new EmptyDependencyContainer() );
		$instance->getContainer()->thisTitleIsEven = $this->newTitle();

		$instance->getContainer()->tanomoshi = function ( DependencyBuilder $builder ) {
			return DIWikiPage::newFromTitle( $builder->newObject( 'thisTitleIsEven' ) );
		};

		$this->assertInstanceOf( 'Title', $instance->newObject( 'thisTitleIsEven' ) );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->newObject( 'tanomoshi' ) );

	}

	/**
	 * @test SimpleDependencyBuilder::getContainer
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObject() {

		$instance = $this->getInstance();
		$instance->getContainer()->registerObject( 'Test', new \stdClass );

		$this->assertInstanceOf( '\stdClass', $instance->newObject( 'Test' ) );

	}

	/**
	 * @test SimpleDependencyBuilder::getContainer
	 * @test SimpleDependencyBuilder::addArgument
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testAddGetArguments() {

		// Add argument using a setter
		$instance = $this->getInstance();
		$title    = $this->newMockObject()->getMockTitle();

		$instance->addArgument( 'Title', $title );
		$instance->getContainer()->registerObject( 'Test', function ( DependencyBuilder $builder ) {
			return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->newObject( 'Test' ) );
		$this->assertInstanceOf( 'Title', $instance->newObject( 'Test' )->getTitle() );

		// Compare added argument and the object provieded by the DI object
		$instance = $this->getInstance();
		$title    = $this->newTitle( NS_MAIN, 'Lala' );

		$instance->addArgument( 'Title', $title );
		$instance->getContainer()->registerObject( 'Test2', function ( DependencyBuilder $builder ) {
			return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertEquals( $title, $instance->newObject( 'Test2' )->getTitle() );

		$instance = $this->getInstance();
		$title    = $this->newTitle( NS_MAIN, 'Lila' );

		$instance->getContainer()->registerObject( 'Test3', function ( DependencyBuilder $builder ) {
			return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertEquals( $title, $instance->newObject( 'Test3', array( $title ) )->getTitle() );

	}

	/**
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testGetArgumentOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->getInstance()->getArgument( 'Title' );

	}

	/**
	 * @test SimpleDependencyBuilder::addArgument
	 *
	 * @since 1.9
	 */
	public function testAddArgumentInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->getInstance()->addArgument( $this->newTitle(), 'Title' );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testNewObjectInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->getInstance()->newObject( new \stdclass );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testUnknownObject() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->getInstance()->newObject( 'Foo' );

	}

}
