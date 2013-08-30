<?php

namespace SMW\Test;

use SMW\EmptyDependencyContainer;
use SMW\SimpleDependencyBuilder;
use SMW\DependencyBuilder;
use SMW\DependencyObject;

use Title;

/**
 * Tests for the SimpleDependencyBuilder
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
 * @covers \SMW\BaseDependencyContainer
 * @covers \SMW\DependencyInjector
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
	 * Helper method that returns a scope definition
	 *
	 * @since 1.9
	 *
	 * @param $data
	 */
	protected function getScopeDefinition( $scope ) {
		$reflector = $this->newReflector( '\SMW\DependencyObject' );
		return $reflector->getConstant( $scope );
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
	private function newDependencyContainer( $toArray = array() ) {

		$container = $this->getMockBuilder( '\SMW\DependencyContainer' )
			->disableOriginalConstructor()
			->setMethods( array(
				'preload',
				'toArray',
				'registerObject',
				'has',
				'get',
				'set',
				'remove',
				'merge',
				'loadObjects'
				)
			)
			->getMock();

		$container->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( $toArray ) );

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
	private function newInstance( $dependencyContainer = null ) {
		return new SimpleDependencyBuilder( $dependencyContainer );
	}

	/**
	 * @test SimpleDependencyBuilder::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test SimpleDependencyBuilder::registerContainer
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterContainer() {

		$instance = $this->newInstance();

		// Register container
		$instance->registerContainer( $this->newDependencyContainer( array( 'Test' => array( '123', 0 ) ) ) );

		$this->assertEquals(
			'123',
			$instance->newObject( 'Test' ),
			'asserts object creation'
		);

		// Register additional container and asserts that both objects are available
		$instance->registerContainer( $this->newDependencyContainer( array( 'Test2' => array( 9001, 1 ) ) ) );

		$this->assertEquals(
			'123',
			$instance->newObject( 'Test' ),
			'asserts object creation after container merge'
		);

		$this->assertEquals(
			9001,
			$instance->newObject( 'Test2' ),
			'asserts object creation after container merge'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::registerContainer
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * Register another container containing the same identifier but
	 * with a different definition
	 *
	 * @since 1.9
	 */
	public function testRegisterContainerWithSameIdentifier() {

		$instance = $this->newInstance();

		$instance->registerContainer( $this->newDependencyContainer( array( 'Test' => array( 9001, 1 ) ) ) );

		$this->assertEquals(
			9001,
			$instance->newObject( 'Test' ),
			'asserts object creation after container merge'
		);

		$instance->registerContainer( $this->newDependencyContainer( array( 'Test' => array( 1009, 0 ) ) ) );

		$this->assertEquals(
			1009,
			$instance->newObject( 'Test' ),
			'asserts object definition has been overridden'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::getContainer
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObject() {

		$instance = $this->newInstance();

		$instance->getContainer()->registerObject( 'Test', new \stdClass );

		$this->assertInstanceOf(
			'\stdClass',
			$instance->newObject( 'Test' ),
			'asserts registration of an object'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObjectUsingMagicMethodEagerLoading() {

		$instance  = $this->newInstance();
		$container = new EmptyDependencyContainer();

		// Eager loading
		$container->someFunnyTitle = $this->newTitle();

		// Register container
		$instance->registerContainer( $container );

		$this->assertInstanceOf(
			'Title',
			$instance->someFunnyTitle(), 'asserts invoked instance'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObjectUsingMagicMethodLazyLoading() {

		$instance  = $this->newInstance();
		$container = new EmptyDependencyContainer();

		$container->someFunnyTitle = $this->newTitle();
		$container->FakeWikiPage = function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		};

		// Register container
		$instance->registerContainer( $container );

		// Adds necessary argument object needed for the FakeWikiPage build process
		$instance->addArgument( 'Title', $instance->someFunnyTitle() );

		$this->assertInstanceOf(
			'\SMW\Test\FakeWikiPage',
			$instance->FakeWikiPage(),
			'asserts invoked instance using __call method'
		);

		$this->assertInstanceOf(
			'\SMW\Test\FakeWikiPage',
			$instance->newObject( 'FakeWikiPage' ),
			'asserts invoked instance using newObject()'
		);

		$this->assertTrue(
			$instance->FakeWikiPage() !== $instance->newObject( 'FakeWikiPage' ),
			'asserts that created instances are different'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObjectUsingMagicMethodViaBuilder() {

		$instance  = $this->newInstance();

		// Clear registered container
		$instance->registerContainer( new EmptyDependencyContainer() );

		// Object is using an argument that where invoked using the __set method
		// and is evenly accessible during the build process using newObject()
		// method
		$instance->getContainer()->quux = $this->newTitle();
		$instance->getContainer()->Baz = function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->newObject( 'quux' ) );
		};

		$this->assertInstanceOf(
			'Title',
			$instance->newObject( 'quux' ),
			'asserts object instance using newObject() method'
		);

		$this->assertInstanceOf(
			'\SMW\Test\FakeWikiPage',
			$instance->newObject( 'Baz' ),
			'asserts object instance using newObject() method'
		);

		$this->assertInstanceOf(
			'\SMW\Test\FakeWikiPage',
			$instance->Baz(),
			'asserts object instance using __call method'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::getContainer
	 * @test SimpleDependencyBuilder::addArgument
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testAddGetArguments() {

		// Add argument using a setter ("real" object)
		$instance = $this->newInstance();
		$title    = $this->newTitle( NS_MAIN, 'Lala' );

		$instance->addArgument( 'Title', $title );
		$instance->getContainer()->registerObject( 'Foo', function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertEquals(
			$title,
			$instance->newObject( 'Foo' )->getTitle(),
			'asserts object instance using newObject() method'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::getContainer
	 * @test SimpleDependencyBuilder::addArgument
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testAddGetArgumentsOnMockObject() {

		$instance  = $this->newInstance();
		$mockTitle = $this->newMockObject()->getMockTitle();

		$instance->addArgument( 'Title', $mockTitle );
		$instance->getContainer()->registerObject( 'bar', function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertInstanceOf(
			'\SMW\Test\FakeWikiPage',
			$instance->newObject( 'bar' ),
			'asserts object instance using newObject() method'
		);

		$this->assertInstanceOf(
			'Title',
			$instance->newObject( 'bar' )->getTitle(),
			'asserts object instance using newObject() method'
		);

		$this->assertInstanceOf(
			'Title',
			$instance->bar()->getTitle(),
			'asserts object instance using __call method'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @dataProvider autoArgumentsDataProvider
	 *
	 * @since 1.9
	 */
	public function testAutoArguments( $setup, $expected ) {

		$instance = $this->newInstance();

		$instance->getContainer()->registerObject( 'Baz', function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		} );

		$this->assertEquals(
			$expected,
			$instance->newObject( 'Baz', $setup )->getTitle(),
			'asserts that newObject() and arguments return expected results'
		);

		$this->assertEquals(
			$expected,
			$instance->Baz( $setup )->getTitle(),
			'asserts that __call and arguments return expected results'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @dataProvider scopeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testCompareScope( $setup, $expected ) {

		$instance  = $this->newInstance();
		$scope     = $this->getScopeDefinition( $setup['scope'] );
		$title     = $this->newTitle( NS_MAIN, 'Lila' );

		// Lazy loading or deferred instantiation
		$instance->getContainer()->registerObject( 'Test', function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		}, $scope );

		$newInstance = $instance->newObject( 'Test', array( $title ) );

		$this->assertEquals(
			$title,
			$newInstance->getTitle(),
			'asserts object instance using newObject() constructor'
		);

		$this->assertEquals(
			$expected,
			$newInstance === $instance->newObject( 'Test', array( $title ) ),
			'asserts whether instances are equal to the selected scope'
		);

		// Eager loading, means that the object is created during initialization and not
		// during execution which is forcing the object to be created instantly
		$instance->getContainer()->registerObject( 'Title', $this->newTitle(), $scope );

		$newInstance = $instance->newObject( 'Title' );

		$this->assertTrue(
			$newInstance === $instance->newObject( 'Title' ),
			'asserts that objects using an eager loading approach are always of prototype scope'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @test SimpleDependencyBuilder::setScope
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testResetScopeLazyLoading() {

		$instance  = $this->newInstance();
		$container = $instance->getContainer();
		$title     = $this->newTitle( NS_MAIN, 'Scope' );

		$instance->getContainer()->registerObject( 'Scope', function ( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		}, DependencyObject::SCOPE_SINGLETON );

		$singleton = $instance->newObject( 'Scope', array( $title ) );
		$this->assertEquals( $title, $singleton->getTitle() );

		$this->assertTrue(
			$singleton === $instance->newObject( 'Scope', array( $title ) ),
			'asserts object instances are of type singleton'
		);

		$prototype = $instance->setScope( DependencyObject::SCOPE_PROTOTYPE )->newObject( 'Scope', array( $title ) );

		$this->assertFalse(
			$prototype === $instance->newObject( 'Scope', array( $title ) ),
			'asserts object scope definition were temporarily altered using setScope()'
		);

		$newSingleton = $instance->newObject( 'Scope', array( $title ) );

		$this->assertTrue(
			$newSingleton === $instance->newObject( 'Scope', array( $title ) ),
			'asserts original object scope has been restored'
		);

		$this->assertTrue(
			$newSingleton === $instance->newObject( 'Scope', array( $title ) ),
			'asserts original object scope has been restored'
		);

		$this->assertTrue(
			$newSingleton === $singleton,
			'asserts original object scope has been restored'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @test SimpleDependencyBuilder::setScope
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testSetScope() {

		$instance  = $this->newInstance();

		$instance->getContainer()->registerObject( 'Scope', function() { return new Title(); },
			DependencyObject::SCOPE_SINGLETON );

		$this->assertTrue(
			$instance->Scope() === $instance->Scope(),
			'asserts object instances are of type singleton'
		);

		$instance->getContainer()->registerObject( 'Scope', function() { return new Title(); },
			DependencyObject::SCOPE_PROTOTYPE );

		$this->assertFalse(
			$instance->Scope() === $instance->newObject( 'Scope' ),
			'asserts object instances are of type prototype'
		);

		$this->assertFalse(
			$instance->setScope( DependencyObject::SCOPE_SINGLETON )->Scope() === $instance->newObject( 'Scope' ),
			'asserts object instances are different'
		);

		$this->assertTrue(
			$instance->setScope( DependencyObject::SCOPE_SINGLETON )->Scope() ===
			$instance->setScope( DependencyObject::SCOPE_SINGLETON )->newObject( 'Scope' ),
			'asserts object instances are of type singleton'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testSetCall() {

		$instance  = $this->newInstance();
		$title     = $this->newTitle( NS_MAIN, 'Lula' );

		$instance->getContainer()->FakeWikiPage = function( DependencyBuilder $builder ) {
			return FakeWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
		};

		$this->assertInstanceOf(
			'Title',
			$instance->FakeWikiPage( $title )->getTitle(),
			'asserts that ...'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testSetCallMultipleArguments() {

		$instance  = $this->newInstance();
		$title1    = $this->newTitle( NS_MAIN, 'Lula' );
		$title2    = $this->newTitle( NS_MAIN, 'Lila' );

		$instance->getContainer()->getArray = function( DependencyBuilder $builder ) {
			return array( $builder->getArgument( 'Title1' ), $builder->getArgument( 'Title2' ) );
		};

		$this->assertEquals(
			array( $title1, $title2 ),
			$instance->addArgument( 'Title1', $title1 )->addArgument( 'Title2', $title2 )->getArray(),
			'asserts that ...'
		);

		$this->assertEquals(
			array( $title1, $title2 ),
			$instance->getArray( array(
				'Title1' => $title1,
				'Title2' => $title2
			) ),
			'asserts that ...'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @dataProvider scopeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testSetCallMagicWordScope( $setup, $expected ) {

		$instance  = $this->newInstance();
		$scope     = $this->getScopeDefinition( $setup['scope'] );
		$title     = $this->newTitle( NS_MAIN, 'Lula' );

		$instance->getContainer()->FakeWikiPage = function() use( $title ) {
			return new FakeWikiPage( $title );
		};

		$this->assertFalse(
			$instance->FakeWikiPage() === $instance->newObject( 'FakeWikiPage' ),
			'asserts that __set/__call itself are always of type SCOPE_PROTOTYPE'
		);

		// If __set/__call is embedded in a SCOPE_SINGLETON call which makes indirectly available
		// through the SINGLETON as it is only executed once during initialization
		$instance->getContainer()->registerObject( 'a1234', function( $builder ) {
			return $builder->FakeWikiPage();
		}, $scope );

		$this->assertEquals(
			$expected,
			$instance->newObject( 'a1234' ) === $instance->a1234(),
			'asserts whether instances are equal to the selected scope'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::registerObject
	 *
	 * @since 1.9
	 */
	public function testRegisterObjectAndRemove() {

		$instance  = $this->newInstance();

		$this->assertFalse(
			$instance->getContainer()->has( 'Title' ),
			'asserts that the container does not have a particular object definition'
		);

		$instance->getContainer()->registerObject( 'Title', $this->newMockObject()->getMockTitle() );

		$this->assertTrue(
			$instance->getContainer()->has( 'Title' ),
			'asserts that after registration the container has a particular object definition'
		);

		$instance->getContainer()->remove( 'Title' );

		$this->assertFalse(
			$instance->getContainer()->has( 'Title' ),
			'asserts that after removal the container does not have a particular object definition'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 * @dataProvider dependencyObjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testDeferredLoading( $setup, $expected ) {

		$container = new FakeDependencyContainer();
		$container->setObjects( array( 'Quux' => $setup ) );

		$instance = $this->newInstance( $container );

		$this->assertEquals(
			$expected,
			$instance->newObject( 'Quux' ),
			'asserts whether object was registered and accessible'
		);

	}

	/**
	 * @test SimpleDependencyBuilder::getArgument
	 *
	 * @since 1.9
	 */
	public function testGetArgumentOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->newInstance()->getArgument( 'Title' );

	}

	/**
	 * @test SimpleDependencyBuilder::hasArgument
	 *
	 * @since 1.9
	 */
	public function testHasArgumentInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->newInstance()->hasArgument( 9001 );

	}

	/**
	 * @test SimpleDependencyBuilder::addArgument
	 *
	 * @since 1.9
	 */
	public function testAddArgumentInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->newInstance()->addArgument( $this->newTitle(), 'Title' );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testNewObjectInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->newInstance()->newObject( new \stdclass );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testNewObjectArgumentsInvalidArgument() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->newInstance()->newObject( 'Test', new \stdclass );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testNewObjectUnknownObject() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->newInstance()->newObject( 'Foo' );

	}

	/**
	 * @test SimpleDependencyBuilder::newObject
	 *
	 * @since 1.9
	 */
	public function testNewObjectDeferredLoadingUnknownObject() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance = $this->newInstance();
		$instance->getContainer()->registerObject( 'DiObjectMapper', array( 'Title' => 'Title' ) );
		$instance->newObject( 'Title' );

		$this->assertTrue( true );
	}

	/**
	 * @return array
	 */
	public function scopeDataProvider() {

		$provider = array();

		$provider[] = array( array( 'scope' => 'SCOPE_SINGLETON' ), true );

		// Inverse behaviour to the previous assert
		$provider[] = array( array( 'scope' => 'SCOPE_PROTOTYPE' ), false );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function autoArgumentsDataProvider() {

		$provider = array();
		$title    = $this->newTitle( NS_MAIN, 'Lala' );

		$provider[] = array( array( $title ), $title );
		$provider[] = array( array( $title, $title ), $title );
		$provider[] = array( array( 'Title' => $title, 'Title2' => $title ), $title );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function dependencyObjectDataProvider() {

		$provider = array();

		$stdClass = new \stdClass;
		$closure  = function() use( $stdClass ) { return $stdClass; };

		// #0
		$dependencyObject = $this->getMockBuilder( '\SMW\DependencyObject' )
			->disableOriginalConstructor()
			->setMethods( array( 'defineObject' ) )
			->getMock();

		$dependencyObject->expects( $this->any() )
			->method( 'defineObject' )
			->will( $this->returnValue( $stdClass ) );

		$provider[] = array( $dependencyObject, $stdClass );

		// #1
		$dependencyObject = $this->getMockBuilder( '\SMW\DependencyObject' )
			->disableOriginalConstructor()
			->setMethods( array( 'defineObject' ) )
			->getMock();

		$dependencyObject->expects( $this->any() )
			->method( 'defineObject' )
			->will( $this->returnValue( $closure ) );

		$provider[] = array( $dependencyObject, $stdClass );

		// #3
		$provider[] = array( $closure, $stdClass );

		return $provider;
	}

}

/**
 * A fake dependency container
 */
class FakeDependencyContainer extends EmptyDependencyContainer {

	protected $objects;

	public function setObjects( $objects ) {
		$this->objects = $objects;
	}

	public function loadObjects() {
		return $this->objects;
	}

}

/**
 * A fake object instance
 */
class FakeWikiPage {

	protected $title = null;

	public function __construct( Title $title ) {
		$this->title = $title;
	}

	public static function newFromTitle( Title $title ) {
		return new self( $title );
	}

	public function getTitle() {
		return $this->title;
	}

}