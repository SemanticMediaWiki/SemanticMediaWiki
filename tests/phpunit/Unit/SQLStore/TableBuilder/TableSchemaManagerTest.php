<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\TableSchemaManager;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableSchemaManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManagerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableSchemaManager::class,
			new TableSchemaManager( $this->store )
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
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTableDefinition ] ) );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$this->store
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
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTableDefinition ] ) );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$this->store
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

	public function testFindTableFullTextTable_Disabled() {

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new TableSchemaManager(
			$this->store
		);

		$this->assertNull(
			$instance->findTable( \SMW\SQLStore\SQLStore::FT_SEARCH_TABLE )
		);
	}

	public function testFindTableFullTextTable_Enabled() {

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new TableSchemaManager(
			$this->store
		);

		$instance->setOptions(
			[
				'smwgEnabledFulltextSearch' => true
			]
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\Table',
			$instance->findTable( \SMW\SQLStore\SQLStore::FT_SEARCH_TABLE )
		);
	}

	public function testPropertyTable_UniqueIndex() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( 'foo_table' ) );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->setMethods( [ 'getTableIndexes' ] )
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( [] ) );

		$dataItemHandler->expects( $this->once() )
			->method( 'getIndexField' )
			->will( $this->returnValue( 'foo' ) );

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->will( $this->returnValue( [ 'foo', [ 'cols', 'type'], 'foo' ] ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTableDefinition ] ) );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$this->store
		);

		$table = $instance->findTable( 'foo_table' );

		$this->assertEquals(
			[
				'sp' => 's_id,p_id',
				'po' => 'foo',
				[ 'cols', 'type' ]
			],
			$table->get( 'indices' )
		);
	}

	public function testPropertyTable_FixedTable() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( 'foo_fixed_table' ) );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->setMethods( [ 'getTableIndexes' ] )
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( [] ) );

		$dataItemHandler->expects( $this->once() )
			->method( 'getIndexField' )
			->will( $this->returnValue( 'bar' ) );

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->will( $this->returnValue( [ 'foo' ] ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTableDefinition ] ) );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$instance = new TableSchemaManager(
			$this->store
		);

		$table = $instance->findTable( 'foo_fixed_table' );

		$this->assertEquals(
			[
				'sp' => 's_id',
				'po' => 'bar',
				'foo',
			],
			$table->get( 'indices' )
		);
	}

}
