<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryClient;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectorsExceptionTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $defaultGraph;

	private $databaseConnectors = [
		'\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector',

		// Legacy and should be removed once obsolete
		'SMWSparqlDatabase4Store',
		'SMWSparqlDatabaseVirtuoso',
		'SMWSparqlDatabase'
	];

	protected function setUp() {
		parent::setUp();

		$this->defaultGraph = 'http://foo/myDefaultGraph';
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testCanConstruct( $httpConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector',
			new $httpConnector( new RepositoryClient( $this->defaultGraph, '' ), $httpRequest )
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoQueryForEmptyQueryEndpointThrowsException( $httpConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '' ),
			$httpRequest
		);

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpEndpointResponseException' );
		$instance->doQuery( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoUpdateForEmptyUpdateEndpointThrowsException( $httpConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '' ),
			$httpRequest
		);

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpEndpointResponseException' );
		$instance->doUpdate( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForEmptyDataEndpointThrowsException( $httpConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', '' ),
			$httpRequest
		);

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpEndpointResponseException' );
		$instance->doHttpPost( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForUnreachableDataEndpointThrowsException( $httpConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->atLeastOnce() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 22 ) );

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', 'unreachableDataEndpoint' ),
			$httpRequest
		);

		$this->setExpectedException( 'Exception' );
		$instance->doHttpPost( '' );
	}

	public function httpDatabaseConnectorInstanceNameProvider() {

		$provider = [];

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = [ $databaseConnector ];
		}

		return $provider;
	}

}
