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

	public function testFindTableMetaTable() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new TableSchemaManager(
			$this->store
		);

		$table = $instance->findTable( SQLStore::META_TABLE );

		$this->assertInstanceOf(
			Table::class,
			$table
		);

		$this->assertSame(
			'smw_meta',
			$table->getName()
		);

		$fields = $table->get( 'fields' );

		$this->assertArrayHasKey( 'meta_key', $fields );
		$this->assertArrayHasKey( 'meta_value', $fields );

		$this->assertSame(
			[ FieldType::TYPE_CHAR_LONG, 'NOT NULL' ],
			$fields['meta_key']
		);

		$this->assertSame(
			[ FieldType::TYPE_BLOB, 'NOT NULL' ],
			$fields['meta_value']
		);

		$indices = $table->get( 'indices' );

		$this->assertSame(
			[ 'meta_key', 'PRIMARY KEY' ],
			$indices['pri']
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

	public function testPropertyTable_RedundantObjectIndexIsDropped() {
		// When the handler declares a composite index that begins with the
		// object index field, the auto-generated single-column "po" index is a
		// redundant left-prefix and must not be created. See issue #6559.
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'foo_wikipage_table' );

		$propertyTableDefinition->method( 'usesIdSubject' )
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
			->willReturn( 'o_id' );

		// The real DIWikiPage index set. Only the standalone 'o_id' must drop;
		// the composites that merely contain o_id in a non-leading position
		// (s_id,o_id and s_id,p_id,o_id) must survive.
		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->willReturn( [ 'o_id', 'p_id,s_id', 's_id,o_id', 's_id,p_id,o_id', 'o_id,s_id', 'o_id,p_id' ] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TableSchemaManager(
			$this->store
		);

		$table = $instance->findTable( 'foo_wikipage_table' );

		$this->assertEquals(
			[
				'sp' => 's_id,p_id',
				'p_id,s_id',
				's_id,o_id',
				's_id,p_id,o_id',
				'o_id,s_id',
				'o_id,p_id',
			],
			$table->get( 'indices' )
		);
	}

	public function testPropertyTable_ObjectIndexRetainedWhenNoCompositeLeadsWithIt() {
		// Counterpart to the drop case: when the object index field only appears
		// in a non-leading position of a composite (the blob/number/uri shape),
		// the single-column "po" index is the sole index serving that column and
		// must be retained. Guards the left-prefix (not substring) semantics.
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'foo_blob_table' );

		$propertyTableDefinition->method( 'usesIdSubject' )
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
			->willReturn( 'o_hash' );

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableIndexes' )
			->willReturn( [ 's_id,o_hash', 'p_id,o_hash' ] );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$this->store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new TableSchemaManager(
			$this->store
		);

		$table = $instance->findTable( 'foo_blob_table' );

		$this->assertEquals(
			[
				'sp' => 's_id,p_id',
				'po' => 'o_hash',
				's_id,o_hash',
				'p_id,o_hash',
			],
			$table->get( 'indices' )
		);
	}

}
