<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMWDIBlob;
use SMW\DIProperty;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use SMW\MediaWiki\Database;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\TableDefinition;
use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\DIWikiPage;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * @covers \SMW\SQLStore\Lookup\EntityUniquenessLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class EntityUniquenessLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp(): void {
		$this->connection = $this->createMock( Database::class );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->iteratorFactory = $this->createMock( IteratorFactory::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityUniquenessLookup::class,
			new EntityUniquenessLookup( $this->store, $this->iteratorFactory )
		);
	}

	public function testCheckConstraint() {
		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_hash' => '' ] );

		$propertyTableInfoFetcher = $this->createMock( PropertyTableInfoFetcher::class );

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'findTableIdForProperty' )
			->willReturn( '_foo' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection', 'getPropertyTables', 'getPropertyTableInfoFetcher', 'getDataItemHandlerForDIType' ] )
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

		$propertyTable = $this->createMock( TableDefinition::class );

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
			->willReturn( \SMWDataItem::TYPE_BLOB );

		$propertyTable->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propertyTable ] );

		$requestOptions = $this->createMock( RequestOptions::class );

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->willReturn( [] );

		$requestOptions->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$connection = $this->createMock( Database::class );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$query = new \SMW\MediaWiki\Connection\Query( $connection );

		$resultWrapper = $this->createMock( IResultWrapper::class );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturn( $resultWrapper );

		$instance = new EntityUniquenessLookup(
			$store,
			$this->iteratorFactory
		);

		$property = $this->createMock( DIProperty::class );

		$dataItem = $this->createMock( SMWDIBlob::class );

		$instance->checkConstraint( $property, $dataItem, $requestOptions );

		$this->assertJsonStringEqualsJsonString(
			'{' .
			'"tables": "smw_foo AS t1",' .
			'"fields":[["t1.s_id"]],' .
			'"conditions":[{"AND":["t1.o_hash="]}],' .
			'"joins":[],' .
			'"options":{"LIMIT":42},"alias":"t","index":1,"autocommit":false}',
			(string)$query
		);
	}

}
