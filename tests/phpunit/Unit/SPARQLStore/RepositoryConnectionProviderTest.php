<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RepositoryConnectionProvider;
use SMW\Tests\Utils\GlobalsProvider;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $globalsProvider;
	private $smwgSparqlCustomConnector;
	private $smwgSparqlRepositoryConnector;

	protected function setUp() {
		parent::setUp();

		$this->globalsProvider = GlobalsProvider::getInstance();

		$this->smwgSparqlRepositoryConnector = $this->globalsProvider->get( 'smwgSparqlRepositoryConnector' );
		$this->smwgSparqlCustomConnector = $this->globalsProvider->get( 'smwgSparqlCustomConnector' );
	}

	protected function tearDown() {

		$this->globalsProvider->set(
			'smwgSparqlRepositoryConnector',
			$this->smwgSparqlRepositoryConnector
		);

		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			$this->smwgSparqlCustomConnector
		);

		$this->globalsProvider->clear();
	}

	public function testCanConstruct() {

		$instance = new RepositoryConnectionProvider();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectionProvider',
			$instance
		);

		$this->assertInstanceOf(
			'\SMW\Connection\ConnectionProvider',
			$instance
		);
	}

	public function testGetDefaultConnection() {

		$instance = new RepositoryConnectionProvider( 'default' );
		$instance->setHttpVersionTo( CURL_HTTP_VERSION_NONE );

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

		$instance = new RepositoryConnectionProvider( 'fuSEKi' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector',
			$instance->getConnection()
		);
	}

	public function testGetVirtuosoConnection() {

		$instance = new RepositoryConnectionProvider( 'virtuoso' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector',
			$instance->getConnection()
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWSparqlDatabaseVirtuoso',
			$instance->getConnection()
		);
	}

	public function testGet4StoreConnection() {

		$instance = new RepositoryConnectionProvider( '4STORE' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector',
			$instance->getConnection()
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWSparqlDatabase4Store',
			$instance->getConnection()
		);
	}

	public function testGetSesameConnection() {

		$instance = new RepositoryConnectionProvider( 'sesame' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector',
			$instance->getConnection()
		);
	}

	public function testGetGenericConnection() {

		$instance = new RepositoryConnectionProvider( 'generic' );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForUnknownConnectorId() {

		$this->globalsProvider->set(
			'smwgSparqlRepositoryConnector',
			'default'
		);

		$instance = new RepositoryConnectionProvider( 'foo' );

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForEmptyConnectorId() {

		$this->globalsProvider->set(
			'smwgSparqlRepositoryConnector',
			'default'
		);

		$instance = new RepositoryConnectionProvider();

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testGetDefaultConnectorForUnMappedId() {

		$this->globalsProvider->set(
			'smwgSparqlRepositoryConnector',
			'idThatCanNotBeMapped'
		);

		$instance = new RepositoryConnectionProvider();

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			$instance->getConnection()
		);
	}

	public function testInvalidCustomClassConnectorThrowsException() {

		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			'InvalidCustomClassConnector'
		);

		$instance = new RepositoryConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

	public function testInvalidCustomRespositoryConnectorThrowsException() {

		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			'\SMW\Tests\Utils\Fixtures\InvalidCustomRespositoryConnector'
		);

		$instance = new RepositoryConnectionProvider( 'custom' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}
