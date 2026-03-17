<?php

namespace SMW\Tests\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\Iterators\AppendIterator;
use SMW\SQLStore\Lookup\ChangePropagationEntityLookup;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\Lookup\ChangePropagationEntityLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationEntityLookupTest extends TestCase {

	private $store;
	private $iteratorFactory;

	private AppendIterator $appendIterator;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->appendIterator = $this->getMockBuilder( AppendIterator::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ChangePropagationEntityLookup::class,
			new ChangePropagationEntityLookup( $this->store, $this->iteratorFactory )
		);
	}

	public function testFindByProperty() {
		$property = new DIProperty( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		$instance = new ChangePropagationEntityLookup(
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
			->willReturn( $this->appendIterator );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'getDefaultDataItemTables' )
			->willReturn( [] );

		$entityIdManager = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID', 'getDataItemPoolHashListFor' ] )
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemPoolHashListFor' )
			->willReturn( [] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$instance = new ChangePropagationEntityLookup(
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
			->willReturn( $this->appendIterator );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->with(
				new DIProperty( '_INST' ),
				$this->anything() )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with(
				$this->anything(),
				new DIProperty( '_SUBC' ) )
			->willReturn( [ DIWikiPage::newFromText( 'Bar' ) ] );

		$instance = new ChangePropagationEntityLookup(
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
		$instance = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$this->expectException( 'RuntimeException' );
		$instance->findAll( 'Foo' );
	}

}
