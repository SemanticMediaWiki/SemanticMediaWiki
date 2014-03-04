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
 * @since 1.9.1.2
 *
 * @author mwjames
 */
class SparqlStoreQueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockForAbstractClass( '\SMW\Store' );

		$this->assertInstanceOf(
			'\SMWSparqlStoreQueryEngine',
			new SMWSparqlStoreQueryEngine( $store )
		);
	}

	public function testGetCountQueryResultWithoutSparqlDatabaseConnection() {

		$store = $this->getMockForAbstractClass( '\SMW\Store' );

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
			'null',
			$instance->getCountQueryResult( $query )
		);
	}

}