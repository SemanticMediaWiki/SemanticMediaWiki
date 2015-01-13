<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\SparqlDBConnectionProvider;
use SMW\Tests\Utils\GlobalsProvider;

/**
 * @covers \SMW\SPARQLStore\SparqlDBConnectionProvider
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SparqlDBConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	private $globalsProvider;
	private $smwgSparqlDatabase;
	private $smwgSparqlDatabaseConnector;

	protected function setUp() {
		parent::setUp();

		$this->globalsProvider = GlobalsProvider::getInstance();

		$this->smwgSparqlDatabaseConnector = $this->globalsProvider->get( 'smwgSparqlDatabaseConnector' );
		$this->smwgSparqlDatabase = $this->globalsProvider->get( 'smwgSparqlDatabase' );
	}

	protected function tearDown() {

		$this->globalsProvider->set(
			'smwgSparqlDatabaseConnector',
			$this->smwgSparqlDatabaseConnector
		);

		$this->globalsProvider->set(
			'smwgSparqlDatabase',
			$this->smwgSparqlDatabase
		);

		$this->globalsProvider->clear();
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

	public function testGetSesameConnection() {

		$instance = new SparqlDBConnectionProvider( 'sesame' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\GenericHttpDatabaseConnector',
			$instance->getConnection()
		);
	}

	public function testGetGenericConnection() {

		$instance = new SparqlDBConnectionProvider( 'generic' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\GenericHttpDatabaseConnector',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForUnknownConnectorId() {

		$this->globalsProvider->set(
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

		$this->globalsProvider->set(
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

		$this->globalsProvider->set(
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

		$this->globalsProvider->set(
			'smwgSparqlDatabase',
			'InvalidCustomClassConnector'
		);

		$instance = new SparqlDBConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

	public function testInvalidCustomSparqlClassConnectorThrowsException() {

		$this->globalsProvider->set(
			'smwgSparqlDatabase',
			'\SMW\Tests\SPARQLStore\InvalidCustomSparqlClassConnector'
		);

		$instance = new SparqlDBConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}

class InvalidCustomSparqlClassConnector {}
