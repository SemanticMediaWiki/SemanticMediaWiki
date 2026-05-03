<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use SMW\Utils\HmacSerializer;

/**
 * @covers \SMW\SQLStore\EntityStore\SequenceMapFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class SequenceMapFinderTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $cache;
	private $idCacheManager;
	private Database $connection;

	protected function setUp(): void {
		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SequenceMapFinder::class,
			new SequenceMapFinder( $this->connection, $this->idCacheManager )
		);
	}

	public function testSetMap() {
		$this->connection->expects( $this->any() )
			->method( 'escape_bytea' )
			->willReturn( '' );

		$tables = $rows = $sets = $uniqueIndexFields = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setMap( 42, [ 'Foo' ] );

		$this->assertSame( [ 'smw_object_aux' ], $tables );
		$this->assertSame(
			[ [ 'smw_id' => 42, 'smw_seqmap' => '' ] ],
			$rows
		);
		$this->assertSame( [ [ 'smw_id' ] ], $uniqueIndexFields );
		$this->assertSame(
			[ [ 'smw_seqmap' => '' ] ],
			$sets
		);
	}

	public function testFindMapById() {
		$map = HmacSerializer::compress( [ 'Foo' ] );

		$row = [
			'smw_id' => 1001,
			'smw_seqmap' => $map
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertEquals(
			[ 'Foo' ],
			$instance->findMapById( 1001 )
		);
	}

	public function testPrefetchSequenceMap() {
		$map = HmacSerializer::compress( [ 'Foo' ] );

		$row = [
			'smw_id' => 1001,
			'smw_seqmap' => $map
		];

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'save' );

		$whereConditions = [];
		$capturedSelects = [];
		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ], $whereConditions, $capturedSelects );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->prefetchSequenceMap( [ 42, 1001 ] );

		$this->assertContains( [ 'smw_id', 'smw_seqmap' ], $capturedSelects );
		$this->assertContains( [ 'smw_id' => [ 42, 1001 ] ], $whereConditions );
	}

}
