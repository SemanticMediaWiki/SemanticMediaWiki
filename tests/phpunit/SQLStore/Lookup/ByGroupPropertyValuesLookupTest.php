<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup;
use SMW\MediaWiki\Connection\Query;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class ByGroupPropertyValuesLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$property = DIProperty::newFromUserLabel( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tablename' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo' => 'id' ] );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

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

		$property = DIProperty::newFromUserLabel( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tablename' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo' => 'id' ] );

		$dataItemHandler->expects( $this->any() )
			->method( 'dataItemFromDBKeys' )
			->willReturn( DIWikiPage::newFromtext( 'Foobar' ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ (object)$row ] );

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

		$property = DIProperty::newFromUserLabel( 'Foo' );
		$property->setPropertyValueType( '_txt' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tablename' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'foo_field' => 'x_type' ] );

		$dataItemHandler->expects( $this->any() )
			->method( 'dataItemFromDBKeys' )
			->willReturn( new \SMWDIBlob( 'test' ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ (object)$row ] );

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

}
