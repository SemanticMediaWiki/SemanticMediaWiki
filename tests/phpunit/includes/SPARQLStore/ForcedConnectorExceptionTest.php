<?php

namespace SMW\Tests\SPARQLStore;

/**
 * @covers \SMWSparqlDatabase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class ForcedConnectorExceptionTest extends \PHPUnit_Framework_TestCase {

	private $defaultGraph;

	protected function setUp() {
		parent::setUp();

		$this->defaultGraph = 'http://example.org/mydefaultgraphname';
	}

	/**
	 * @dataProvider httpConnectorNameProvider
	 */
	public function testCanConstruct( $httpConnector ) {

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			new $httpConnector( $this->defaultGraph, '' )
		);
	}

	/**
	 * @dataProvider httpConnectorNameProvider
	 */
	public function testDoUpdateForEmptyUpdateEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			''
		);

		$this->setExpectedException( '\SMW\SPARQLStore\BadHttpDatabaseResponseException' );
		$instance->doUpdate( '' );
	}

	/**
	 * @dataProvider httpConnectorNameProvider
	 */
	public function testDoHttpPostForEmptyDataEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			'',
			''
		);

		$this->setExpectedException( '\SMW\SPARQLStore\BadHttpDatabaseResponseException' );
		$instance->doHttpPost( '' );
	}

	/**
	 * @dataProvider httpConnectorNameProvider
	 */
	public function testDoHttpPostForUnreachableDataEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			'',
			'unreachableDataEndpoint'
		);

		$this->setExpectedException( 'Exception' );
		$instance->doHttpPost( '' );
	}

	public function httpConnectorNameProvider() {

		$provider = array(
			array( 'SMWSparqlDatabase' ),
			array( '\SMW\SPARQLStore\FusekiHttpDatabaseConnector' ),
			array( 'SMWSparqlDatabase4Store' ),
			array( 'SMWSparqlDatabaseVirtuoso' )
		);

		return $provider;
	}

}
