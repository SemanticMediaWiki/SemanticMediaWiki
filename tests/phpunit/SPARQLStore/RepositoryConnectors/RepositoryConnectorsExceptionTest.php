<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use Onoi\HttpRequest\HttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectorsExceptionTest extends TestCase {

	private $defaultGraph;

	private $databaseConnectors = [
		GenericRepositoryConnector::class,
		FusekiRepositoryConnector::class,
		FourstoreRepositoryConnector::class,
		VirtuosoRepositoryConnector::class,
	];

	protected function setUp(): void {
		parent::setUp();

		$this->defaultGraph = 'http://foo/myDefaultGraph';
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testCanConstruct( $httpConnector ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			GenericRepositoryConnector::class,
			new $httpConnector( new RepositoryClient( $this->defaultGraph, '' ), $httpRequest )
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoQueryForEmptyQueryEndpointThrowsException( $httpConnector ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '' ),
			$httpRequest
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doQuery( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoUpdateForEmptyUpdateEndpointThrowsException( $httpConnector ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '' ),
			$httpRequest
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doUpdate( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForEmptyDataEndpointThrowsException( $httpConnector ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', '' ),
			$httpRequest
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doHttpPost( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForUnreachableDataEndpointThrowsException( $httpConnector ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->atLeastOnce() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', 'unreachableDataEndpoint' ),
			$httpRequest
		);

		$this->expectException( 'Exception' );
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
