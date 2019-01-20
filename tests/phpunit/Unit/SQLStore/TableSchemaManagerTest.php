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

}
