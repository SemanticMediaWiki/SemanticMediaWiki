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

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$conceptQueryResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConceptQueryResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ConceptCache',
			new ConceptCache( $this->store, $conceptQueryResolver )
		);
	}

	public function testRefreshConceptCache() {

		$conceptQueryResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConceptQueryResolver' )
			->disableOriginalConstructor()
			->getMock();

		$conceptQueryResolver->expects( $this->once() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$instance = new ConceptCache(
			new \SMWSQLStore3(),
			$conceptQueryResolver
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

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = new \SMWSQLStore3();
		$store->setConnectionManager( $connectionManager );

		$conceptQueryResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConceptQueryResolver' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptCache(
			$store,
			$conceptQueryResolver
		);

		$instance->deleteConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);
	}

}
