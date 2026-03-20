<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use Onoi\Cache\FixedInMemoryLruCache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DisplayTitleFinder;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\CacheWarmer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class CacheWarmerTest extends TestCase {

	private $idCacheManager;
	private $store;
	private $cache;

	protected function setUp(): void {
		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new FixedInMemoryLruCache();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CacheWarmer::class,
			new CacheWarmer( $this->store, $this->idCacheManager )
		);
	}

	public function testPrepareCache_Page() {
		$list = [
			new WikiPage( 'Bar', NS_MAIN )
		];

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'Foo',
			'smw_sort' => '',
		];

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 'smw_hash' => [ '7b6b944694382bfab461675f40a2bda7e71e68e3' ] ] )
			->willReturn( [ (object)$row ] );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

	public function testPrepareCache_DisplayTitleFinder() {
		$displayTitleFinder = $this->getMockBuilder( DisplayTitleFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$displayTitleFinder->expects( $this->once() )
			->method( 'prefetchFromList' );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setDisplayTitleFinder( $displayTitleFinder );
		$instance->setThresholdLimit( 1 );

		$instance->prepareCache( [] );
	}

	public function testPrepareCache_Property() {
		$list = [
			// Both represent the same object hence only cache once
			new Property( 'Foo' ),
			new WikiPage( 'Foo', SMW_NS_PROPERTY )
		];

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'Foo',
			'smw_sort' => '',
		];

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 'smw_hash' => [ '909d8ab26ea49adb7e1b106bc47602050d07d19f' ] ] )
			->willReturn( [ (object)$row ] );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

	public function testPrepareCache_UnknownPredefinedProperty() {
		$list = [
			new WikiPage( '_Foo', SMW_NS_PROPERTY )
		];

		$this->idCacheManager->expects( $this->never() )
			->method( 'setCache' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

}
