<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ByGroupPropertyValuesLookupTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ByGroupPropertyValuesLookup::class,
			new ByGroupPropertyValuesLookup( $this->store )
		);
	}

	public function testFetchGroup_Empty() {
		$property = Property::newFromUserLabel( 'Foo' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo' => 'id' ] );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new ByGroupPropertyValuesLookup(
			$this->store
		);

		$res = $instance->findValueGroups( $property, [ 'foo', 'bar' ] );

		$this->assertIsArray(

			$res
		);
	}

	public function testFetchGroup_PageResult() {
		$row = [
			'smw_title' => 'Foobar',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_sort' => 'FOOBAR',
			'smw_subobject' => '',
			'count' => 42
		];

		$property = Property::newFromUserLabel( 'Foo' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo' => 'id' ] );

		$dataItemHandler->expects( $this->any() )
			->method( 'dataItemFromDBKeys' )
			->willReturn( WikiPage::newFromtext( 'Foobar' ) );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [ (object)$row ] ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new ByGroupPropertyValuesLookup(
			$this->store
		);

		$res = $instance->findValueGroups( $property, [ 'foo', 'bar' ] );

		$this->assertEquals(
			[
				'groups' => [ 'Foobar' => 42 ],
				'raw' => [ 'Foobar' => 'Foobar' ]
			],
			$res
		);
	}

	public function testFetchGroup_NonPageResult() {
		$row = [
			'foo_field' => '1001',
			'count' => 42
		];

		$property = Property::newFromUserLabel( 'Foo' );
		$property->setPropertyValueType( '_txt' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo_field' => 'x_type' ] );

		$dataItemHandler->expects( $this->any() )
			->method( 'dataItemFromDBKeys' )
			->willReturn( new Blob( 'test' ) );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [ (object)$row ] ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new ByGroupPropertyValuesLookup(
			$this->store
		);

		$res = $instance->findValueGroups( $property, [ 'foo', 'bar' ] );

		$this->assertEquals(
			[
				'groups' => [ 'test' => 42 ],
				'raw' => [ 'test' => 'test' ]
			],
			$res
		);
	}

	/**
	 * Creates a mock SelectQueryBuilder where all chained methods return $this
	 * and fetchResultSet() returns the given rows wrapped in FakeResultWrapper.
	 */
	private function createMockSelectQueryBuilder( array $rows ) {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'select', 'from', 'join', 'where', 'groupBy', 'orderBy', 'caller' ];

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
