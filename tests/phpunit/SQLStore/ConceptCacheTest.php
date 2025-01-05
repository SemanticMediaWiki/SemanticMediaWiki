<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ConceptCache;
use Title;

/**
 * @covers \SMW\SQLStore\ConceptCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptCacheTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $conceptQuerySegmentBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->conceptQuerySegmentBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\ConceptCache',
			new ConceptCache( $this->store, $this->conceptQuerySegmentBuilder )
		);
	}

	public function testRefreshConceptCache() {
		$this->conceptQuerySegmentBuilder->expects( $this->once() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new ConceptCache(
			new \SMWSQLStore3(),
			$this->conceptQuerySegmentBuilder
		);

		$instance->refreshConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);
	}

	public function testDeleteConceptCache() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$connection->expects( $this->once() )
			->method( 'delete' );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = new \SMWSQLStore3();
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
