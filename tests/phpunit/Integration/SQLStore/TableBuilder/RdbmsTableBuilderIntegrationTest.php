<?php

namespace SMW\Tests\Integration\SQLStore\TableBuilder;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\SQLStore\TableBuilder\RdbmsTableBuilder;
use SMW\SQLStore\TableBuilder\PostgresRdbmsTableBuilder;
use SMW\SQLStore\TableBuilder\SQLiteRdbmsTableBuilder;
use Onoi\MessageReporter\MessageReporterFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RdbmsTableBuilderIntegrationTest extends MwDBaseUnitTestCase {

	private $messageReporterFactory;
	private $rdbmsTableBuilder;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->messageReporterFactory = MessageReporterFactory::getInstance();

		$this->rdbmsTableBuilder = RdbmsTableBuilder::factory(
			$this->getStore()->getConnection( DB_MASTER )
		);

		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCreateTable() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$definition = array(
			'fields' => array(
				'id'   => $this->rdbmsTableBuilder->getStandardFieldType( 'id primary' ),
				't_text' => $this->rdbmsTableBuilder->getStandardFieldType( 'blob' ),
			),
			'wgDBname' => $GLOBALS['wgDBname'],
			'wgDBTableOptions' => $GLOBALS['wgDBTableOptions']
		);

		$this->rdbmsTableBuilder->createTable( 'rdbms_test', $definition );

		$expected = array(
			'Checking table rdbms_test',
			'Table not found, now creating',
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testUpdateTableWithNewField() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$definition = array(
			'fields' => array(
				'id'   => $this->rdbmsTableBuilder->getStandardFieldType( 'id primary' ),
				't_text' => $this->rdbmsTableBuilder->getStandardFieldType( 'blob' ),
				't_num'  => $this->rdbmsTableBuilder->getStandardFieldType( 'integer' ),
				't_int'  => $this->rdbmsTableBuilder->getStandardFieldType( 'integer' )
			),
			'wgDBname' => $GLOBALS['wgDBname'],
			'wgDBTableOptions' => $GLOBALS['wgDBTableOptions']
		);

		$this->rdbmsTableBuilder->createTable( 'rdbms_test', $definition );

		$expected = array(
			'Checking table rdbms_test',
			'Table already exists, checking structure',
			'creating field t_num',
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testUpdateTableWithNewFieldType() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$definition = array(
			'fields' => array(
				'id'   => $this->rdbmsTableBuilder->getStandardFieldType( 'id primary' ),
				't_text' => $this->rdbmsTableBuilder->getStandardFieldType( 'blob' ),
				't_num'  => $this->rdbmsTableBuilder->getStandardFieldType( 'double' ),
				't_int'  => $this->rdbmsTableBuilder->getStandardFieldType( 'integer' )
			),
			'wgDBname' => $GLOBALS['wgDBname'],
			'wgDBTableOptions' => $GLOBALS['wgDBTableOptions']
		);

		$this->rdbmsTableBuilder->createTable( 'rdbms_test', $definition );

		$expected = array(
			'Checking table rdbms_test',
			'Table already exists, checking structure',
			'changing type of field t_num',
		);

		// Not supported
		if ( $this->rdbmsTableBuilder instanceof SQLiteRdbmsTableBuilder ) {
			$expected = str_replace( 'changing type of field t_num', 'changing field type is not supported', $expected );
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testCreateIndex() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$definition = array(
			'indicies' => array(
				'id',
				't_num'
			)
		);

		$this->rdbmsTableBuilder->createIndex( 'rdbms_test', $definition );

		$expected = array(
			'Checking index structures for table rdbms_test',
			'index id is fine',
			'creating new index t_num'
		);

		// ID message, Primary doesn't implicitly exists before
		if ( $this->rdbmsTableBuilder instanceof PostgresRdbmsTableBuilder || $this->rdbmsTableBuilder instanceof SQLiteRdbmsTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testUpdateExistingIndex() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$definition = array(
			'indicies' => array(
				'id',
				't_num,t_int'
			)
		);

		$this->rdbmsTableBuilder->createIndex( 'rdbms_test', $definition );

		$expected = array(
			'Checking index structures for table rdbms_test',
			'index id is fine',
			'removing index t_num',
			'creating new index t_num,t_int',
		);

		if ( $this->rdbmsTableBuilder instanceof SQLiteRdbmsTableBuilder ) {
			$expected = str_replace( 'index id is fine', 'creating new index id', $expected );
			$expected = 'removing index';
		}

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testDropTable() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$this->rdbmsTableBuilder->dropTable( 'rdbms_test' );

		$expected = array(
			'dropped table rdbms_test'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

	public function testTryToDropTableWhichNotExists() {

		$messageReporter = $this->messageReporterFactory->newSpyMessageReporter();

		$this->rdbmsTableBuilder->setMessageReporter(
			$messageReporter
		);

		$this->rdbmsTableBuilder->dropTable( 'foo_test' );

		$expected = array(
			'foo_test not found, skipping removal'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$messageReporter->getMessagesAsString()
		);
	}

}
