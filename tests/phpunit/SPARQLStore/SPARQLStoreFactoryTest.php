<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\SPARQLStoreFactory;

/**
 * @covers \SMW\SPARQLStore\SPARQLStoreFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class SPARQLStoreFactoryTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $repositoryConnection );
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
			$instance->getBaseStore( 'SMWSQLStore3' )
		);
	}

	public function testCanConstructMasterQueryEngine() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newMasterQueryEngine()
		);
	}

	public function testCanConstructConnectionManager() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\Connection\ConnectionManager',
			$instance->getConnectionManager()
		);
	}

	public function testCanConstructRepositoryRedirectLookup() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryRedirectLookup',
			$instance->newRepositoryRedirectLookup()
		);
	}

	public function testCanConstructReplicationDataTruncator() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\ReplicationDataTruncator',
			$instance->newReplicationDataTruncator()
		);
	}

}
