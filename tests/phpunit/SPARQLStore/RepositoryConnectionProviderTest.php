<?php

namespace SMW\Tests\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionProvider;
use SMW\SPARQLStore\RepositoryConnectionProvider;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;
use SMW\Tests\Utils\Fixtures\InvalidCustomRespositoryConnector;
use SMW\Tests\Utils\GlobalsProvider;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionProviderTest extends TestCase {

	private $globalsProvider;
	private $smwgSparqlCustomConnector;
	private $smwgSparqlRepositoryConnector;

	protected function setUp(): void {
		parent::setUp();

		$this->globalsProvider = GlobalsProvider::getInstance();

		$this->smwgSparqlRepositoryConnector = $this->globalsProvider->get( 'smwgSparqlRepositoryConnector' );
		$this->smwgSparqlCustomConnector = $this->globalsProvider->get( 'smwgSparqlCustomConnector' );
	}

	protected function tearDown(): void {
		$this->globalsProvider->set(
			'smwgSparqlRepositoryConnector',
			$this->smwgSparqlRepositoryConnector
		);

		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			$this->smwgSparqlCustomConnector
		);
	}

	public function testCanConstruct() {
		$instance = new RepositoryConnectionProvider();

		$this->assertInstanceOf(
			RepositoryConnectionProvider::class,
			$instance
		);

		$this->assertInstanceOf(
			ConnectionProvider::class,
			$instance
		);
	}

	public function testGetDefaultConnection() {
		$instance = new RepositoryConnectionProvider( 'default' );
		$instance->setHttpVersionTo( CURL_HTTP_VERSION_NONE );

		$this->assertInstanceOf(
			GenericRepositoryConnector::class,
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
			FusekiRepositoryConnector::class,
			$instance->getConnection()
		);
	}

	public function testGetVirtuosoConnection() {
		$instance = new RepositoryConnectionProvider( 'virtuoso' );

		$this->assertInstanceOf(
			VirtuosoRepositoryConnector::class,
			$instance->getConnection()
		);
	}

	public function testGet4StoreConnection() {
		$instance = new RepositoryConnectionProvider( '4STORE' );

		$this->assertInstanceOf(
			FourstoreRepositoryConnector::class,
			$instance->getConnection()
		);
	}

	public function testGetSesameConnection() {
		$instance = new RepositoryConnectionProvider( 'sesame' );

		$this->assertInstanceOf(
			GenericRepositoryConnector::class,
			$instance->getConnection()
		);
	}

	public function testGetGenericConnection() {
		$instance = new RepositoryConnectionProvider( 'generic' );

		$this->assertInstanceOf(
			GenericRepositoryConnector::class,
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
			GenericRepositoryConnector::class,
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
			GenericRepositoryConnector::class,
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
			GenericRepositoryConnector::class,
			$instance->getConnection()
		);
	}

	public function testInvalidCustomClassConnectorThrowsException() {
		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			'InvalidCustomClassConnector'
		);

		$instance = new RepositoryConnectionProvider( 'custom' );

		$this->expectException( 'RuntimeException' );
		$instance->getConnection();
	}

	public function testInvalidCustomRespositoryConnectorThrowsException() {
		$this->globalsProvider->set(
			'smwgSparqlCustomConnector',
			InvalidCustomRespositoryConnector::class
		);

		$instance = new RepositoryConnectionProvider( 'custom' );

		$this->expectException( 'RuntimeException' );
		$instance->getConnection();
	}

}
