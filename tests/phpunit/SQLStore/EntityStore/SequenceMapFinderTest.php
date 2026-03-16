<?php

namespace SMW\Tests\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
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
		$row = [
			'smw_id' => 42,
			'smw_seqmap' => null
		];

		$this->connection->expects( $this->once() )
			->method( 'upsert' )
			->with(
				$this->anything(),
				$row,
				'smw_id',
				[ 'smw_seqmap' => null ] );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setMap( 42, [ 'Foo' ] );
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

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$sortkey = '';

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

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id', 'smw_seqmap' ] ),
				$this->equalTo( [ 'smw_id' => [ 42, 1001 ] ] ) )
			->willReturn( [ (object)$row ] );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->prefetchSequenceMap( [ 42, 1001 ] );
	}

}
