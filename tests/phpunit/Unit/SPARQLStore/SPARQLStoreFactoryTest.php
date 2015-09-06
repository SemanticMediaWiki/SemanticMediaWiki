<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\SPARQLStoreFactory;

/**
 * @covers \SMW\SPARQLStore\SPARQLStoreFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SPARQLStoreFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $repositoryConnection ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\SPARQLStoreFactory',
			new SPARQLStoreFactory( $this->store )
		);
	}

	public function testCanConstructBaseStore() {

		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMWSQLStore3',
			$instance->newBaseStore( 'SMWSQLStore3' )
		);
	}

	public function testCanConstructMasterQueryEngine() {

		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			$instance->newMasterQueryEngine()
		);
	}

	public function testCanConstructConnectionManager() {

		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\ConnectionManager',
			$instance->newConnectionManager()
		);
	}

}
