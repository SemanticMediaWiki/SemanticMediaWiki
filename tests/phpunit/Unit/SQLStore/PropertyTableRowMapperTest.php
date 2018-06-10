<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableRowMapper;

/**
 * @covers \SMW\SQLStore\PropertyTableRowMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableRowMapperTest extends \PHPUnit_Framework_TestCase {

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
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowMapper(
			$store
		);

		$result = $instance->mapToRows(
			42,
			$semanticData
		);

		$this->assertInternalType(
			'array',
			$result
		);
	}

	public function testMapToRowsWithFixedProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'makeSMWPropertyID', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->will( $this->returnValue( 9999 ) );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'Foo_test_123'),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'smw_foo' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

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

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'makeSMWPropertyID', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'makeSMWPropertyID' )
			->will( $this->returnValue( 9999 ) );

		$idTable->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'Foo_test_123'),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$propertyTables = [ 'smw_foo' => $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables', 'findPropertyTableID', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'smw_foo' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

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
