<?php

namespace SMW\Tests\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use Onoi\Cache\FixedInMemoryLruCache;
use SMW\Utils\HmacSerializer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\AuxiliaryFields
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AuxiliaryFieldsTest extends \PHPUnit\Framework\TestCase {

	private $connection;
	private $idCacheManager;
	private Cache $cache;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new FixedInMemoryLruCache();
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

		$subjects = [ DIWikiPage::newFromText( 'Foo' ) ];

		$row = [
			'smw_id' => 42,
			'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6',
			'smw_countmap' => 0
		];

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 't.smw_hash' => [ 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' ] ] )
			->willReturn( [ (object)$row ] );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\FieldList',
			$instance->prefetchFieldList( $subjects )
		);
	}

	public function testSetFieldMaps_Empty() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->once() )
			->method( 'upsert' )
			->with(
				$this->anything(),
				$this->equalTo( [
					'smw_id' => 42,
					'smw_seqmap' => null,
					'smw_countmap' => null ] ) );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [], [] );
	}

	public function testSetFieldMaps() {
		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( AuxiliaryFields::COUNTMAP_CACHE_ID )
			->willReturn( $this->cache );

		$this->connection->expects( $this->any() )
			->method( 'escape_bytea' )
			->willReturnArgument( 0 );

		$this->connection->expects( $this->once() )
			->method( 'upsert' )
			->with(
				$this->anything(),
				$this->equalTo( [
					'smw_id' => 42,
					'smw_seqmap' => HmacSerializer::compress( [ 'seqmap' ] ),
					'smw_countmap' => HmacSerializer::compress( [ 'countmap' ] ) ] ) );

		$instance = new AuxiliaryFields(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setFieldMaps( 42, [ 'seqmap' ], [ 'countmap' ] );
	}

}
