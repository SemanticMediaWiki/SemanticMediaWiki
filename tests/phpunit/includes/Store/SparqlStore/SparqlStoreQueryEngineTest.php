<?php

namespace SMW\Tests\Store\SparqlStore;

use SMWSparqlStoreQueryEngine;
use SMW\DIProperty;

/**
 * @covers \SMWSparqlStoreQueryEngine
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SparqlStoreQueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockForAbstractClass( '\SMWSparqlStore' );

		$this->assertInstanceOf(
			'\SMWSparqlStoreQueryEngine',
			new SMWSparqlStoreQueryEngine( $store )
		);
	}

	public function testGetCountQueryResultOnMockSparqlDatabaseConnection() {

		$sparqlResultWrapper = $this->getMockBuilder( '\SMWSparqlResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->once() )
			->method( 'selectCount' )
			->will( $this->returnValue( $sparqlResultWrapper ) );

		$store = $this->getMockBuilder( '\SMWSparqlStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getSparqlDatabase' )
			->will( $this->returnValue( $sparqlDatabase ) );

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$somePropertyDescription = $this->getMockBuilder( '\SMWSomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$somePropertyDescription->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( new DIProperty( __METHOD__ ) ) );

		$somePropertyDescription->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $somePropertyDescription ) );

		$instance = new SMWSparqlStoreQueryEngine( $store );

		$this->assertInternalType(
			'integer',
			$instance->getCountQueryResult( $query )
		);
	}

}