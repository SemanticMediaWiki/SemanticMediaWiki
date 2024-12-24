<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\DIWikiPage;

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
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
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
		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_hash' => '' ] );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

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

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
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
			->willReturn( \SMWDataItem::TYPE_BLOB );

		$propertyTable->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'smw_foo' );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propertyTable ] );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->willReturn( [] );

		$requestOptions->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$query = new \SMW\MediaWiki\Connection\Query( $connection );

		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

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

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

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
