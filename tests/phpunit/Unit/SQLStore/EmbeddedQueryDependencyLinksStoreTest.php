<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\EmbeddedQueryDependencyLinksStore;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMWQuery as Query;

/**
 * @covers \SMW\SQLStore\EmbeddedQueryDependencyLinksStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyLinksStoreTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getWikiPageLastModifiedTimestamp' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getWikiPageLastModifiedTimestamp' )
			->will( $this->returnValue( 0 ) );

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EmbeddedQueryDependencyLinksStore',
			new EmbeddedQueryDependencyLinksStore( $store )
		);
	}

	public function testPruneOutdatedTargetLinks() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$this->assertTrue(
			$instance->pruneOutdatedTargetLinks( $compositePropertyTableDiffIterator )
		);
	}

	public function testPruneOutdatedTargetLinksBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$this->assertNull(
			$instance->pruneOutdatedTargetLinks( $compositePropertyTableDiffIterator )
		);
	}

	public function testBuildParserCachePurgeJobParameters() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->any() )
			->method( 'getCombinedIdListForChangedEntities' )
			->will( $this->returnValue( array( 1, 2 ) ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$this->assertEquals(
			array( 'idlist' => array( 1, 2 ) ),
			$instance->buildParserCachePurgeJobParametersFrom( $compositePropertyTableDiffIterator )
		);
	}

	public function testBuildParserCachePurgeJobParametersBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$this->assertEmpty(
			$instance->buildParserCachePurgeJobParametersFrom( $compositePropertyTableDiffIterator )
		);
	}

	public function testFindPartialEmbeddedQueryTargetLinksHashListFor() {

		$row = new \stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getDataItemPoolHashListFor' ) )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemPoolHashListFor' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->anything(),
				$this->equalTo( array( 'o_id' => array( 42 ) ) ) )
			->will( $this->returnValue( array( $row ) ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$instance->findPartialEmbeddedQueryTargetLinksHashListFor( array( 42 ), 1, 200 );
	}

	public function testAddDependenciesFromQueryResultBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$this->assertNull(
			$instance->addDependenciesFromQueryResult( '' )
		);
	}

	public function testAddDependenciesFromQueryResult() {

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ValueDescription( DIWikiPage::newFromText( 'Bar' ) )
		);

		$query = new Query(
			$description
		);

		$query->setSubject( DIWikiPage::newFromText( __METHOD__ ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( array( 's_id' => 42 ) ) );

		$insert[] = array(
			's_id' => 42,
			'o_id' => 1001
		);

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( $insert ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->addDependenciesFromQueryResult( $queryResult );
	}

}
