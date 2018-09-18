<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ConceptCache;
use Title;

/**
 * @covers \SMW\SQLStore\ConceptCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptCacheTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $conceptQuerySegmentBuilder;

	protected function setUp() {
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
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( false ) );

		$connection->expects( $this->once() )
			->method( 'delete' );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

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
