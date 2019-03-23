<?php

namespace SMW\Tests\SQLStore\EntityStore;

use Onoi\Cache\FixedInMemoryLruCache;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\EntityStore\CacheWarmer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class CacheWarmerTest extends \PHPUnit_Framework_TestCase {

	private $idCacheManager;
	private $store;
	private $cache;

	protected function setUp() {

		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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

	public function testFillFromList_Page() {

		$list = [
			new DIWikiPage( 'Bar', NS_MAIN )
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
			->will( $this->returnValue( $this->cache ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 'smw_hash' => [ '7b6b944694382bfab461675f40a2bda7e71e68e3' ] ]) )
			->will( $this->returnValue( [ (object)$row ] ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->fillFromList( $list );
	}

	public function testFillFromList_Property() {

		$list = [
			// Both represent the same object hence only cache once
			new DIProperty( 'Foo' ),
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY )
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
			->will( $this->returnValue( $this->cache ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 'smw_hash' => [ '909d8ab26ea49adb7e1b106bc47602050d07d19f' ] ]) )
			->will( $this->returnValue( [ (object)$row ] ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->fillFromList( $list );
	}

}
