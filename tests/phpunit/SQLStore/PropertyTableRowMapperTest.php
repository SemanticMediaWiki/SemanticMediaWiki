<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTableRowMapper
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableRowMapperTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowMapper',
			new PropertyTableRowMapper( $store )
		);
	}

	public function testMapToRowsOnEmptyTable() {
		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$instance = new PropertyTableRowMapper(
			$store
		);

		$result = $instance->mapToRows(
			42,
			$semanticData
		);

		$this->assertIsArray(

			$result
		);
	}

	public function testMapToRowsWithFixedProperty() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->willReturn( 9999 );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'Foo_test_123' ),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( 'smw_foo' );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$instance = new PropertyTableRowMapper(
			$store
		);

		list( $rows, $textItems, $propertyList, $fixedPropertyList ) = $instance->mapToRows(
			42,
			$semanticData
		);

		$this->assertArrayHasKey(
			'Foo_test_123',
			$propertyList
		);

		$this->assertArrayHasKey(
			'smw_foo',
			$fixedPropertyList
		);
	}

	public function testNewChangeOp() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->willReturn( 9999 );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'Foo_test_123' ),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->willReturn( 'smw_foo' );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$instance = new PropertyTableRowMapper(
			$store
		);

		$changeOp = $instance->newChangeOp(
			42,
			$semanticData
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$changeOp
		);
	}

}
