<?php

namespace SMW\Tests\Integration\SQLStore;

use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataModel\SemanticData;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \SMW\SQLStore\SQLStore
 *
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class UpdateFixedPropertyTablesDBIntegrationTest extends SMWIntegrationTestCase {

	private SQLStore $store;
	private IDatabase $connection;
	private string $tableName;
	private string $propertyLabel;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyLabel = 'Fixed property of ' . $this->getName();
		$this->testEnvironment->addConfiguration( 'smwgFixedProperties', [ $this->propertyLabel ] );

		$this->store = $this->getStore();
		$this->store->clear();
		$this->connection = $this->store->getConnection( DB_PRIMARY );
		$this->tableName = $this->store->findPropertyTableID( $this->newProperty() );

		$this->createTable( [ 'o_id' => FieldType::FIELD_ID ] );
	}

	protected function tearDown(): void {
		$this->connection->query( 'DROP TABLE ' . $this->connection->tableName( $this->tableName ), __METHOD__ );
		$this->store->clear();

		parent::tearDown();
	}

	public function testEmptyTableGetsColumnsOfDeclaredType() {
		$this->declareType( '_txt' );

		$this->store->updateFixedPropertyTables();

		$this->assertSame( [ 'o_blob', 'o_hash', 's_id' ], $this->getColumns() );
	}

	public function testTableWithValuesKeepsColumnsAndValues() {
		$this->declareType( '_txt' );
		$this->connection->insert( $this->tableName, [ 's_id' => 42, 'o_id' => 43 ], __METHOD__ );

		$this->store->updateFixedPropertyTables();

		$this->assertSame( [ 'o_id', 's_id' ], $this->getColumns() );
		$this->assertSame(
			'43',
			$this->connection->selectField( $this->tableName, 'o_id', [ 's_id' => 42 ], __METHOD__ )
		);
	}

	public function testUpToDateTableKeepsAdditionalIndex() {
		$this->declareType( '_wpg' );
		$this->connection->query(
			'CREATE INDEX additional_index ON ' . $this->connection->tableName( $this->tableName ) . ' (o_id)',
			__METHOD__
		);

		$this->store->updateFixedPropertyTables();

		$this->assertTrue(
			$this->connection->indexExists( $this->tableName, 'additional_index', __METHOD__ )
		);
	}

	public function testEmptyTableKeepsColumnsWhileNoTypeIsStored() {
		$this->createTable( [ 'o_blob' => FieldType::TYPE_BLOB, 'o_hash' => FieldType::FIELD_TITLE ] );

		$this->store->updateFixedPropertyTables();

		$this->assertSame( [ 'o_blob', 'o_hash', 's_id' ], $this->getColumns() );
	}

	public function testTypeWithoutDataItemHandlerLeavesTableUnchanged() {
		$this->declareType( '_undefined' );

		$this->store->updateFixedPropertyTables();

		$this->assertSame( [ 'o_id', 's_id' ], $this->getColumns() );
	}

	private function newProperty(): Property {
		return Property::newFromUserLabel( $this->propertyLabel );
	}

	private function createTable( array $valueColumns ): void {
		$table = new Table( $this->tableName );
		$table->addColumn( 's_id', FieldType::FIELD_ID );

		foreach ( $valueColumns as $name => $type ) {
			$table->addColumn( $name, $type );
		}

		TableBuilder::factory( $this->connection )->create( $table );
	}

	private function declareType( string $typeId ): void {
		$semanticData = new SemanticData( $this->newProperty()->getDiWikiPage() );
		$semanticData->addPropertyObjectValue(
			new Property( Property::TYPE_HAS_TYPE ),
			new Uri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', $typeId )
		);

		$this->store->updateData( $semanticData );
		$this->store->clear();
	}

	/**
	 * @return string[]
	 */
	private function getColumns(): array {
		$columns = array_filter(
			[ 'o_blob', 'o_hash', 'o_id', 's_id' ],
			fn ( string $column ) => $this->connection->fieldExists( $this->tableName, $column, __METHOD__ )
		);

		return array_values( $columns );
	}

}
