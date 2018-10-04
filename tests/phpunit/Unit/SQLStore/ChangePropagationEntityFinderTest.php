<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\ChangePropagationEntityFinder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\ChangePropagationEntityFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationEntityFinderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $iteratorFactory;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->appendIterator = $this->getMockBuilder( '\SMW\Iterators\AppendIterator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangePropagationEntityFinder::class,
			new ChangePropagationEntityFinder( $this->store, $this->iteratorFactory )
		);
	}

	public function testFindByProperty() {

		$property = new DIProperty( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->will( $this->returnValue( $this->appendIterator ) );

		$instance = new ChangePropagationEntityFinder(
			$this->store,
			$this->iteratorFactory
		);

		$this->assertInstanceOf(
			'Iterator',
			$instance->findByProperty( $property )
		);
	}

	public function testFindByProperty_TypePropagation() {

		$property = new DIProperty( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->will( $this->returnValue( $this->appendIterator ) );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'getDefaultDataItemTables' )
			->will( $this->returnValue( [] ) );

		$entityIdManager = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID', 'getDataItemPoolHashListFor' ] )
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemPoolHashListFor' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$instance = new ChangePropagationEntityFinder(
			$store,
			$this->iteratorFactory
		);

		$instance->isTypePropagation( true );

		$res = $instance->findByProperty( $property );

		$this->assertInstanceOf(
			'Iterator',
			$res
		);

		$this->assertEquals(
			$res,
			$instance->findAll( $property )
		);
	}

	public function testFindByCategory() {

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->will( $this->returnValue( $this->appendIterator ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_INST' ) ),
				$this->anything() )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with(
				$this->anything(),
				$this->equalTo( new DIProperty( '_SUBC' ) ) )
			->will( $this->returnValue( [ DIWikiPage::newFromText( 'Bar' ) ] ) );

		$instance = new ChangePropagationEntityFinder(
			$store,
			$this->iteratorFactory
		);

		$res = $instance->findByCategory( $category );

		$this->assertInstanceOf(
			'Iterator',
			$res
		);

		$this->assertEquals(
			$res,
			$instance->findAll( $category )
		);
	}

	public function testFindAllOnUnknownTypeThrowsException() {

		$instance = new ChangePropagationEntityFinder(
			$this->store,
			$this->iteratorFactory
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->findAll( 'Foo' );
	}

}
