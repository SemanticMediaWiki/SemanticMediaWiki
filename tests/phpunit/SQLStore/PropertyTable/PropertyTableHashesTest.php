<?php

namespace SMW\Tests\SQLStore\PropertyTable;

use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\StoreFactory;
use SMWDataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTable\PropertyTableHashes
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PropertyTableHashesTest extends \PHPUnit\Framework\TestCase {

	private $connection;
	private $idCacheManager;
	private $cache;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
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

		$rows = [
			'smw_proptable_hash' => 'a:1:{i:0;s:3:"foo";}'
		];

		$expected = [
			'smw_id' => 42
		];

		$this->connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$rows,
				$expected );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setPropertyTableHashes( 42, [ 'foo' ] );
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

		$fields = [
			'smw_proptable_hash'
		];

		$expected = [
			'smw_id' => 42
		];

		$row = [
			'smw_proptable_hash' => 'a:1:{i:0;s:3:"foo";}'
		];

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$fields,
				$expected )
			->willReturn( (object)$row );

		$instance = new PropertyTableHashes(
			$this->connection,
			$this->idCacheManager
		);

		$this->assertEquals(
			[ 'foo' ],
			$instance->getPropertyTableHashesById( 42 )
		);
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
