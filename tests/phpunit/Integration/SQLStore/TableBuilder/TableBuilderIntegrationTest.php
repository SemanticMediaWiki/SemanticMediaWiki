<?php

namespace SMW\Tests\Integration\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\PostgresTableBuilder;
use SMW\SQLStore\TableBuilder\SQLiteTableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Tests\MwDBaseUnitTestCase;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableBuilderIntegrationTest extends MwDBaseUnitTestCase {

	private $spyMessageReporter;
	private $TableBuilder;
	private $stringValidator;
	private $tableName = 'rdbms_test';

	protected function setUp() {
		parent::setUp();

		$this->tableBuilder = TableBuilder::factory(
			$this->getStore()->getConnection( DB_MASTER )
		);

		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCreateTable() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking table rdbms_test',
			'Table not found, now creating',
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateTableWithNewField() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_INT, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking table rdbms_test',
			'Table already exists, checking structure',
			'creating field t_num',
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateTableWithNewFieldType() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_DOUBLE, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking table rdbms_test',
			'Table already exists, checking structure',
			'changing type of field t_num',
		];

		// Not supported
		if ( $this->tableBuilder instanceof SQLiteTableBuilder ) {
			$expected = str_replace( 'changing type of field t_num', 'changing field type is not supported', $expected );
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCreateIndex() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_DOUBLE, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$table->addIndex( 'id' );
		$table->addIndex( 't_int' );
		$table->addIndex( 't_num' );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking index structures for table rdbms_test',
			'index id is fine',
			'creating new index t_num'
		];

		// ID message, Primary doesn't implicitly exists before
		if ( $this->tableBuilder instanceof PostgresTableBuilder || $this->tableBuilder instanceof SQLiteTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateIndex_ReplaceIndex() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_DOUBLE, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$table->addIndex( 'id' );
		$table->addIndex( 't_num,t_int' );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking index structures for table rdbms_test',
			'index id is fine',
			'removing index t_num',
			'creating new index t_num,t_int',
		];

		if ( $this->tableBuilder instanceof SQLiteTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
			$expected = 'removing index';
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateIndex_RemoveIndexWithArrayNotation() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_DOUBLE, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$table->addIndex( [ 'id', 'UNIQUE INDEX' ] );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking index structures for table rdbms_test',
			'index id is fine',
			'removing index t_num,t_int'
		];

		if ( $this->tableBuilder instanceof SQLiteTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
			$expected = 'removing index';
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testUpdateIndex_NoIndexChange() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );
		$table->addColumn( 'id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 't_text', [ FieldType::TYPE_BLOB, 'NOT NULL' ] );
		$table->addColumn( 't_num', [ FieldType::TYPE_DOUBLE, 'NOT NULL' ] );
		$table->addColumn( 't_int', [ FieldType::TYPE_INT ] );

		$table->addIndex( [ 'id', 'UNIQUE INDEX' ] );

		$this->tableBuilder->create( $table );

		$expected = [
			'Checking index structures for table rdbms_test',
			'index id is fine'
		];

		if ( $this->tableBuilder instanceof SQLiteTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
			$expected = 'removing index';
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testDropTable() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( $this->tableName );

		$this->tableBuilder->drop( $table );

		$expected = [
			'dropping table rdbms_test'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testTryToDropTableWhichNotExists() {

		$this->tableBuilder->setMessageReporter(
			$this->spyMessageReporter
		);

		$table = new Table( 'foo_test' );

		$this->tableBuilder->drop( $table );

		$expected = [
			'foo_test not found, skipping removal'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
