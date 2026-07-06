<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\PropertyTableRowMapper
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableRowMapperTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			PropertyTableRowMapper::class,
			new PropertyTableRowMapper( $store )
		);
	}

	public function testMapToRowsOnEmptyTable() {
		$subject = new WikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables' ] )
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
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->willReturn( 9999 );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$subject = new WikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new Property( 'Foo_test_123' ),
			new WikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
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

		[ $rows, $textItems, $propertyList, $fixedPropertyList ] = $instance->mapToRows(
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
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->willReturn( 9999 );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$subject = new WikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new Property( 'Foo_test_123' ),
			new WikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
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
			ChangeOp::class,
			$changeOp
		);
	}

}
