<?php

namespace SMW\Tests\Unit\SQLStore;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\ConceptCache;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\ConceptCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptCacheTest extends TestCase {

	private $store;
	private $conceptQuerySegmentBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->conceptQuerySegmentBuilder = $this->getMockBuilder( ConceptQuerySegmentBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptCache::class,
			new ConceptCache( $this->store, $this->conceptQuerySegmentBuilder )
		);
	}

	public function testRefreshConceptCache() {
		$this->conceptQuerySegmentBuilder->expects( $this->once() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new ConceptCache(
			new SQLStore(),
			$this->conceptQuerySegmentBuilder
		);

		$instance->refreshConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);
	}

	public function testDeleteConceptCache() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$connection->expects( $this->once() )
			->method( 'delete' );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = new SQLStore();
		$store->setConnectionManager( $connectionManager );

		$instance = new ConceptCache(
			$store,
			$this->conceptQuerySegmentBuilder
		);

		$instance->deleteConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);
	}

}
