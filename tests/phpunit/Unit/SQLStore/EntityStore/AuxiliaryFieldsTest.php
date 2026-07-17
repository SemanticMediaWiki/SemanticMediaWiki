<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use SMW\SQLStore\EntityStore\FieldList;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use SMW\Utils\HmacSerializer;

/**
 * @covers \SMW\SQLStore\EntityStore\AuxiliaryFields
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AuxiliaryFieldsTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $connection;
	private $idCacheManager;
	private InMemoryLruCache $cache;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new InMemoryLruCache();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AuxiliaryFields::class,
			new AuxiliaryFields( $this->connection, $this->idCacheManager )
		);
	}

	public function testPrefetchFieldList() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$subjects = [ WikiPage::newFromText( 'Foo' ) ];

		$row = [
			'smw_id' => 42,
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ),
			'smw_countmap' => 0
		];

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertInstanceOf(
			FieldList::class,
			$instance->prefetchFieldList( $subjects )
		);

		$this->assertContains(
			[ 't.smw_hash' => [ sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ) ] ],
			$whereConditions
		);
	}

	public function testPrefetchFieldListWithCorruptBlobCachesEmptyMap() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$subjects = [ WikiPage::newFromText( 'Foo' ) ];

		// A stored count map that cannot be deserialized (e.g. written under a
		// different $wgSecretKey) must degrade to an empty map, not a false that
		// FieldList then iterates with `foreach` (T7043, count map sibling).
		$row = [
			'smw_id' => 42,
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ),
			'smw_countmap' => 'not-a-deserializable-blob'
		];

		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$fieldList = $instance->prefetchFieldList( $subjects );

		// The corrupt blob is cached as an empty map (not false) ...
		$this->assertSame( [], $this->cache->fetch( '42' ) );

		// ... so the FieldList can be iterated without a `foreach` over a bool.
		$this->assertSame( [], $fieldList->getCountListByType( 'foo' ) );
	}

	public function testPrefetchFieldListTreatsEmptyByteaAsEmptyMapWithoutWarning() {
		// PostgreSQL's unescape_bytea() returns '' (not null) for a NULL column,
		// so an empty count map must be recognised as empty rather than run
		// through uncompress() and reported as a deserialization failure.
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'unescape_bytea' )
			->willReturn( '' );

		$subjects = [ WikiPage::newFromText( 'Foo' ) ];

		$row = [
			'smw_id' => 42,
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ),
			'smw_countmap' => null
		];

		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->never() )
			->method( 'warning' );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);
		$instance->setLogger( $logger );

		$instance->prefetchFieldList( $subjects );

		$this->assertSame( [], $this->cache->fetch( '42' ) );
	}

	public function testPrefetchFieldListLogsWarningOnCorruptBlob() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$subjects = [ WikiPage::newFromText( 'Foo' ) ];

		$row = [
			'smw_id' => 42,
			'smw_hash' => sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ),
			'smw_countmap' => 'not-a-deserializable-blob'
		];

		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'warning' )
			->with(
				$this->stringContains( 'could not be deserialized' ),
				$this->callback( static fn ( $context ) => ( $context['id'] ?? null ) === 42 )
			);

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);
		$instance->setLogger( $logger );

		$instance->prefetchFieldList( $subjects );
	}

	public function testSetFieldMaps_Empty() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$tables = $rows = $sets = $uniqueIndexFields = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [], [] );

		$this->assertSame( [ 'smw_object_aux' ], $tables );
		$this->assertSame(
			[ [ 'smw_id' => 42, 'smw_seqmap' => null, 'smw_countmap' => null ] ],
			$rows
		);
		$this->assertSame( [ [ 'smw_id' ] ], $uniqueIndexFields );
		$this->assertSame(
			[ [ 'smw_seqmap' => null, 'smw_countmap' => null ] ],
			$sets
		);
	}

	public function testSetFieldMaps() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'escape_bytea' )
			->willReturnArgument( 0 );

		$tables = $rows = $sets = $uniqueIndexFields = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [ 'seqmap' ], [ 'countmap' ] );

		$this->assertSame( [ 'smw_object_aux' ], $tables );
		$this->assertSame(
			[ [
				'smw_id' => 42,
				'smw_seqmap' => HmacSerializer::compress( [ 'seqmap' ] ),
				'smw_countmap' => HmacSerializer::compress( [ 'countmap' ] )
			] ],
			$rows
		);
		$this->assertSame( [ [ 'smw_id' ] ], $uniqueIndexFields );
		$this->assertSame(
			[ [
				'smw_seqmap' => HmacSerializer::compress( [ 'seqmap' ] ),
				'smw_countmap' => HmacSerializer::compress( [ 'countmap' ] )
			] ],
			$sets
		);
	}

}
