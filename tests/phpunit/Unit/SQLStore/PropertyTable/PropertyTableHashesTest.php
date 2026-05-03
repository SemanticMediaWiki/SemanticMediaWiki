<?php

namespace SMW\Tests\Unit\SQLStore\PropertyTable;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\PropertyTable\PropertyTableHashes
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PropertyTableHashesTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $connection;
	private $idCacheManager;
	private $cache;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableHashes::class,
			new PropertyTableHashes( $this->connection, $this->idCacheManager )
		);
	}

	public function testSetPropertyTableHashes() {
		$this->cache->expects( $this->once() )
			->method( 'save' );

		$this->idCacheManager->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->cache );

		$updateTables = $updateSets = $updateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder( $updateTables, $updateSets, $updateWheres );

		$this->connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setPropertyTableHashes( 42, [ 'foo' ] );

		$this->assertSame( [ 'smw_object_ids' ], $updateTables );
		$this->assertSame( [ [ 'smw_proptable_hash' => 'a:1:{i:0;s:3:"foo";}' ] ], $updateSets );
		$this->assertSame( [ [ 'smw_id' => 42 ] ], $updateWheres );
	}

	public function testGetPropertyTableHashes() {
		$this->cache->expects( $this->once() )
			->method( 'save' );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->idCacheManager->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->cache );

		$row = [
			'smw_proptable_hash' => 'a:1:{i:0;s:3:"foo";}'
		];

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertEquals(
			[ 'foo' ],
			$instance->getPropertyTableHashesById( 42 )
		);

		$this->assertContains( [ 'smw_id' => 42 ], $whereConditions );
	}

	public function testSetPropertyTableHashesCache() {
		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				42,
				[ 'foo' ] );

		$this->idCacheManager->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->cache );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setPropertyTableHashesCache( 42, 'a:1:{i:0;s:3:"foo";}' );
	}

	public function testSetPropertyTableHashesCache_Zero() {
		$this->idCacheManager->expects( $this->never() )
			->method( 'get' )
			->willReturn( $this->cache );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setPropertyTableHashesCache( 0 );
	}

	public function testClearPropertyTableHashCacheById() {
		$this->idCacheManager->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->cache );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				42,
				[] );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$instance->clearPropertyTableHashCacheById( 42 );
	}

}
