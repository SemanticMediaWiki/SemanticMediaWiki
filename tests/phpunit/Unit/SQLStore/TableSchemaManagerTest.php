<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableSchemaManager;

/**
 * @covers \SMW\SQLStore\TableSchemaManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TableSchemaManager::class,
			new TableSchemaManager( $store )
		);
	}

	public function testGetTablesWithEmptyPropertyTableDefinition() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $propertyTableDefinition ) ) );

		$store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$store
		);

		$this->assertInternalType(
			'array',
			$instance->getTables()
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function testFindTableDefinitionWithNoCaseFeature() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $propertyTableDefinition ) ) );

		$store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$store
		);

		$instance->setFeatureFlags(
			SMW_FIELDT_CHAR_NOCASE
		);

		$table = $instance->findTable( \SMW\SQLStore\SQLStore::ID_TABLE );
		$fields = $table->get( 'fields' );

		$this->assertContains(
			FieldType::TYPE_CHAR_NOCASE,
			$fields['smw_sortkey']
		);
	}

}
