<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Tests\Util\IteratorMockBuilder;

use SMW\SPARQLStore\QueryEngine\SparqlResultConverter;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\SparqlResultConverter
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
class SparqlResultConverterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\SparqlResultConverter',
			new SparqlResultConverter( $store )
		);
	}

	/**
	 * @dataProvider sparqlResultWrapperErrorCodeProvider
	 */
	public function testGetQueryResultObjectForCountQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$sparqlResultWrapper = $this->getMockBuilder( '\SMWSparqlResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlResultWrapper->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$instance = new SparqlResultConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);

		$this->assertCountQueryResultForErrorCode(
			$errorCode,
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);
	}

	/**
	 * @dataProvider sparqlResultWrapperErrorCodeProvider
	 */
	public function testGetQueryResultObjectForEmptyInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$sparqlResultWrapper = $this->getMockBuilder( '\SMWSparqlResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlResultWrapper->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new SparqlResultConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);

		$this->assertInstanceQueryResultForErrorCode(
			$errorCode,
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);
	}

	/**
	 * @dataProvider sparqlResultWrapperErrorCodeProvider
	 */
	public function testGetQueryResultObjectForInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$expElement = $this->getMockBuilder( '\SMWExpElement' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$iteratorMockBuilder = new IteratorMockBuilder();

		$sparqlResultWrapper = $iteratorMockBuilder->setClass( '\SMWSparqlResultWrapper' )
			->with( array( array( $expElement ) ) )
			->getMockForIterator();

		$sparqlResultWrapper->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new SparqlResultConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);

		$this->assertInstanceQueryResultForErrorCode(
			$errorCode,
			$instance->convertToQueryResult( $sparqlResultWrapper, $query )
		);
	}

	private function assertCountQueryResultForErrorCode( $errorCode, QueryResult $queryResult ) {

		if ( $errorCode > 0 ) {
			$this->assertNotEmpty( $queryResult->getErrors() );
			return $this->assertNull( $queryResult->getCountValue() );
		}

		$this->assertEmpty( $queryResult->getErrors() );
		$this->assertInternalType( 'integer', $queryResult->getCountValue() );
	}

	private function assertInstanceQueryResultForErrorCode( $errorCode, QueryResult $queryResult ) {

		if ( $errorCode > 0 ) {
			return $this->assertNotEmpty( $queryResult->getErrors() );
		}

		$this->assertEmpty( $queryResult->getErrors() );
	}

	public function sparqlResultWrapperErrorCodeProvider() {

		$provider = array(
			array( 0 ),
			array( 1 ),
			array( 2 )
		);

		return $provider;
	}

}
