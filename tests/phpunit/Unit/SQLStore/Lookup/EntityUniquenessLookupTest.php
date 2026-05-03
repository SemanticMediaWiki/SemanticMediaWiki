<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\Lookup\EntityUniquenessLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class EntityUniquenessLookupTest extends TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityUniquenessLookup::class,
			new EntityUniquenessLookup( $this->store, $this->iteratorFactory )
		);
	}

	public function testCheckConstraint() {
		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_hash' => '' ] );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'findTableIdForProperty' )
			->willReturn( '_foo' );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getPropertyTables', 'getPropertyTableInfoFetcher', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$propertyTable->expects( $this->any() )
			->method( 'getFixedProperty' )
			->willReturn( '_UNKNOWN_FIXED_PROPERTY' );

		$propertyTable->expects( $this->once() )
			->method( 'getDiType' )
			->willReturn( DataItem::TYPE_BLOB );

		$propertyTable->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propertyTable ] );

		$requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->willReturn( [] );

		$requestOptions->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

		$instance = new EntityUniquenessLookup(
			$store,
			$this->iteratorFactory
		);

		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( Blob::class )
			->disableOriginalConstructor()
			->getMock();

		$mappingIterator = $this->getMockBuilder( \SMW\Iterators\MappingIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newMappingIterator' )
			->willReturn( $mappingIterator );

		$result = $instance->checkConstraint( $property, $dataItem, $requestOptions );

		$this->assertSame( $mappingIterator, $result );
	}

	/**
	 * Creates a mock SelectQueryBuilder where chained methods return $this
	 * and fetchResultSet() returns the given rows wrapped in FakeResultWrapper.
	 */
	private function createMockSelectQueryBuilder( array $rows ) {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'select', 'from', 'join', 'leftJoin', 'where',
			'andWhere', 'limit', 'caller' ];

		foreach ( $chainMethods as $method ) {
			$queryBuilder->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$queryBuilder->expects( $this->any() )
			->method( 'fetchResultSet' )
			->willReturn( new FakeResultWrapper( $rows ) );

		return $queryBuilder;
	}

}
