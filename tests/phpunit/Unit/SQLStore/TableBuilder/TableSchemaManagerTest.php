<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableSchemaManager;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableSchemaManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManagerTest extends TestCase {

	private $store;
	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableSchemaManager::class,
			new TableSchemaManager( $this->store )
		);
	}

	public function testGetTablesWithEmptyPropertyTableDefinition() {
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->willReturn( [] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TableSchemaManager(
			$this->store
		);

		$this->assertIsArray(

			$instance->getTables()
		);

		$this->assertIsString(

			$instance->getHash()
		);
	}

	public function testFindTableDefinitionWithNoCaseFeature() {
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->willReturn( [] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TableSchemaManager(
			$this->store
		);

		$instance->setFeatureFlags(
			SMW_FIELDT_CHAR_NOCASE
		);

		$table = $instance->findTable( SQLStore::ID_TABLE );
		$fields = $table->get( 'fields' );

		$this->assertContains(
			FieldType::TYPE_CHAR_NOCASE,
			$fields['smw_sortkey']
		);
	}

	public function testFindTableFullTextTable_Disabled() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new TableSchemaManager(
			$this->store
		);

		$this->assertNull(
			$instance->findTable( SQLStore::FT_SEARCH_TABLE )
		);
	}

	public function testFindTableFullTextTable_Enabled() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new TableSchemaManager(
			$this->store
		);

		$instance->setOptions(
			[
				'smwgEnabledFulltextSearch' => true
			]
		);

		$this->assertInstanceOf(
			Table::class,
			$instance->findTable( SQLStore::FT_SEARCH_TABLE )
		);
	}

	public function testPropertyTable_UniqueIndex() {
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'foo_table' );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getTableIndexes' ] )
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->willReturn( [] );

		$dataItemHandler->expects( $this->once() )
			->method( 'getIndexField' )
			->willReturn( 'foo' );

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->willReturn( [ 'foo', [ 'cols', 'type' ], 'foo' ] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

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
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'foo_fixed_table' );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTableDefinition->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getTableIndexes' ] )
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->willReturn( [] );

		$dataItemHandler->expects( $this->once() )
			->method( 'getIndexField' )
			->willReturn( 'bar' );

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->willReturn( [ 'foo' ] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

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
