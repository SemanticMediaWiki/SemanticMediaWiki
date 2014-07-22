<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\SparqlDBConnectionProvider;
use SMW\Configuration\Configuration;

/**
 * @covers \SMW\SPARQLStore\SparqlDBConnectionProvider
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SparqlDBConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	private $configuration;
	private $smwgSparqlDatabase;
	private $smwgSparqlDatabaseConnector;

	protected function setUp() {
		parent::setUp();

		$this->configuration = Configuration::getInstance();

		$this->smwgSparqlDatabaseConnector = $this->configuration->get( 'smwgSparqlDatabaseConnector' );
		$this->smwgSparqlDatabase = $this->configuration->get( 'smwgSparqlDatabase' );
	}

	protected function tearDown() {

		$this->configuration->set(
			'smwgSparqlDatabaseConnector',
			$this->smwgSparqlDatabaseConnector
		);

		$this->configuration->set(
			'smwgSparqlDatabase',
			$this->smwgSparqlDatabase
		);
	}

	public function testCanConstruct() {

		$instance = new SparqlDBConnectionProvider();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\SparqlDBConnectionProvider',
			$instance
		);

		$this->assertInstanceOf(
			'\SMW\DBConnectionProvider',
			$instance
		);
	}

	public function testGetDefaultConnection() {

		$instance = new SparqlDBConnectionProvider( 'default' );

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);

		$connection = $instance->getConnection();

		$this->assertSame(
			$connection,
			$instance->getConnection()
		);

		$instance->releaseConnection();

		$this->assertNotSame(
			$connection,
			$instance->getConnection()
		);
	}

	public function testGetFusekiConnection() {

		$instance = new SparqlDBConnectionProvider( 'fuSEKi' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\FusekiHttpDatabaseConnector',
			$instance->getConnection()
		);
	}

	public function testGetVirtuosoConnection() {

		$instance = new SparqlDBConnectionProvider( 'virtuoso' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\VirtuosoHttpDatabaseConnector',
			$instance->getConnection()
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWSparqlDatabaseVirtuoso',
			$instance->getConnection()
		);
	}

	public function testGet4StoreConnection() {

		$instance = new SparqlDBConnectionProvider( '4STORE' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\FourstoreHttpDatabaseConnector',
			$instance->getConnection()
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWSparqlDatabase4Store',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForUnknownConnectorId() {

		$this->configuration->set(
			'smwgSparqlDatabaseConnector',
			'default'
		);

		$instance = new SparqlDBConnectionProvider( 'foo' );

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForEmptyConnectorId() {

		$this->configuration->set(
			'smwgSparqlDatabaseConnector',
			'default'
		);

		$instance = new SparqlDBConnectionProvider();

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForUnMappedId() {

		$this->configuration->set(
			'smwgSparqlDatabaseConnector',
			'idThatCanNotBeMapped'
		);

		$instance = new SparqlDBConnectionProvider();

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testInvalidCustomClassConnectorThrowsException() {

		$this->configuration->set(
			'smwgSparqlDatabase',
			'InvalidCustomClassConnector'
		);

		$instance = new SparqlDBConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

	public function testInvalidCustomSparqlClassConnectorThrowsException() {

		$this->configuration->set(
			'smwgSparqlDatabase',
			'\SMW\Tests\SPARQLStore\InvalidCustomSparqlClassConnector'
		);

		$instance = new SparqlDBConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}

class InvalidCustomSparqlClassConnector {}
