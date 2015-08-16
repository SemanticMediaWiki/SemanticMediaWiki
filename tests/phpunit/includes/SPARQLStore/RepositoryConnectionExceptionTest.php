<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RepositoryClient;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionExceptionTest extends \PHPUnit_Framework_TestCase {

	private $defaultGraph;

	private $databaseConnectors = array(
		'\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector',

		// Legacy and should be removed once obsolete
		'SMWSparqlDatabase4Store',
		'SMWSparqlDatabaseVirtuoso',
		'SMWSparqlDatabase'
	);

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
			'\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$provider = array();

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = array( $databaseConnector );
		}

		return $provider;
	}

}
