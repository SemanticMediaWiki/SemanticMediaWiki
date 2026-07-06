<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\EntityStore\IdEntityFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class IdEntityFinderTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $testEnvironment;
	private $cache;
	private $iteratorFactory;
	private $idCacheManager;
	private $store;
	private Database $connection;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		// A real in-process cache rather than a mock: getDataItemById()
		// round-trips through it, so behaviour is asserted on the cached state.
		$this->cache = new InMemoryLruCache();

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IdEntityFinder::class,
			new IdEntityFinder( $this->store, $this->iteratorFactory, $this->idCacheManager )
		);
	}

	public function testGetDataItemForNonCachedId() {
		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject = '';
		$row->smw_sortkey = '';
		$row->smw_sort = '';
		$row->smw_hash = 'x99w';

		// The cache starts empty, so the lookup misses and reads from the table.
		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new IdEntityFinder(
			$this->store,
			$this->iteratorFactory,
			$this->idCacheManager
		);

		$dataItem = $instance->getDataItemById( 42 );

		$this->assertInstanceOf(
			WikiPage::class,
			$dataItem
		);

		$this->assertContains( [ 'smw_id' => 42 ], $whereConditions );

		// The freshly fetched row is written back into the cache under its id.
		$this->assertSame( $dataItem, $this->cache->fetch( '42' ) );
	}

	public function testGetDataItemForCachedId() {
		$cached = new WikiPage( 'Foo', NS_MAIN );

		// Pre-populate the cache so the lookup is served without a table read.
		$this->cache->save( '42', $cached );

		$this->connection->expects( $this->never() )
			->method( 'newSelectQueryBuilder' );

		$instance = new IdEntityFinder(
			$this->store,
			$this->iteratorFactory,
			$this->idCacheManager
		);

		$this->assertSame(
			$cached,
			$instance->getDataItemById( 42 )
		);
	}

	public function testPredefinedPropertyItem() {
		$dataItem = new WikiPage( '_MDAT', SMW_NS_PROPERTY );
		$dataItem->setId( 42 );
		$dataItem->setSortKey( 'bar' );
		$dataItem->setOption( 'sort', 'BAR' );

		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_title = '_MDAT';
		$row->smw_namespace = SMW_NS_PROPERTY;
		$row->smw_iw = '';
		$row->smw_subobject = '';
		$row->smw_sortkey = 'bar';
		$row->smw_sort = 'BAR';
		$row->smw_hash = 'x99w';

		// The cache starts empty, so the lookup misses and reads from the table.
		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new IdEntityFinder(
			$this->store,
			$this->iteratorFactory,
			$this->idCacheManager
		);

		$this->assertEquals(
			$dataItem,
			$instance->getDataItemById( 42 )
		);

		$this->assertContains( [ 'smw_id' => 42 ], $whereConditions );
	}

	public function testNullForUnknownId() {
		// The cache starts empty, so the lookup misses and reads from the table.
		$qb = $this->createMockSelectQueryBuilder( [] );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new IdEntityFinder(
			$this->store,
			$this->iteratorFactory,
			$this->idCacheManager
		);

		$this->assertNull(
			$instance->getDataItemById( 42 )
		);
	}

	public function testGetDataItemsFromList() {
		$expected = new WikiPage( 'Foo', 0, '', '' );
		$expected->setId( 42 );
		$expected->setSortKey( '...' );
		$expected->setOption( 'sort', '...' );

		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject = '';
		$row->smw_sortkey = '...';
		$row->smw_sort = '...';
		$row->smw_hash = 'x99w';

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new IdEntityFinder(
			$this->store,
			new IteratorFactory(),
			$this->idCacheManager
		);

		foreach ( $instance->getDataItemsFromList( [ 42 ] ) as $value ) {
			$this->assertEquals(
				$expected,
				$value
			);
		}

		$this->assertContains( [ 'smw_id' => [ 42 ] ], $whereConditions );
	}

}
