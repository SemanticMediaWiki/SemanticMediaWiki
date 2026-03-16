<?php

namespace SMW\Tests\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\QueryEngine;
use SMW\SPARQLStore\ReplicationDataTruncator;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\RepositoryRedirectLookup;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SPARQLStore\SPARQLStoreFactory;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SPARQLStore\SPARQLStoreFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class SPARQLStoreFactoryTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SPARQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $repositoryConnection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SPARQLStoreFactory::class,
			new SPARQLStoreFactory( $this->store )
		);
	}

	public function testCanConstructBaseStore() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			SQLStore::class,
			$instance->getBaseStore( SQLStore::class )
		);
	}

	public function testCanConstructMasterQueryEngine() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			QueryEngine::class,
			$instance->newMasterQueryEngine()
		);
	}

	public function testCanConstructConnectionManager() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ConnectionManager::class,
			$instance->getConnectionManager()
		);
	}

	public function testCanConstructRepositoryRedirectLookup() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			RepositoryRedirectLookup::class,
			$instance->newRepositoryRedirectLookup()
		);
	}

	public function testCanConstructReplicationDataTruncator() {
		$instance = new SPARQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			ReplicationDataTruncator::class,
			$instance->newReplicationDataTruncator()
		);
	}

}
