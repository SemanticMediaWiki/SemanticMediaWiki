<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\MediaWiki\Connection\Query;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityIdFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class EntityIdFinderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $cache;
	private $propertyTableHashes;
	private $idCacheManager;
	private $conection;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( $this->cache ) );

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableHashes = $this->getMockBuilder( '\SMW\SQLStore\PropertyTable\PropertyTableHashes' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			EntityIdFinder::class,
			new EntityIdFinder( $this->connection, $this->propertyTableHashes, $this->idCacheManager )
		);
	}

	public function testFindIdByItem() {

		$row = [
			'smw_id' => 42
		];

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( false ) );

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$instance = new EntityIdFinder(
			$this->connection,
			$this->propertyTableHashes,
			$this->idCacheManager
		);

		$this->assertEquals(
			42,
			$instance->findIdByItem( $dataItem )
		);
	}

	public function testFetchFieldsFromTableById() {

		$row = [
			'smw_id' => 42,
			'smw_hash' => '00000000000',
			'smw_sort' => 'sort_a',
			'smw_sortkey' => 'sort_b'
		];

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$instance = new EntityIdFinder(
			$this->connection,
			$this->propertyTableHashes,
			$this->idCacheManager
		);

		$sortkey = '';

		$this->assertEquals(
			[ 42, 'sort_b' ],
			$instance->fetchFieldsFromTableById( 42, 'foo', 0, '', '', $sortkey )
		);
	}

	public function testFetchFromTableByTitle() {

		$row = [
			'smw_id' => 42,
			'smw_hash' => '00000000000',
			'smw_sort' => 'sort_a',
			'smw_sortkey' => 'sort_b'
		];

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$instance = new EntityIdFinder(
			$this->connection,
			$this->propertyTableHashes,
			$this->idCacheManager
		);

		$sortkey = '';

		$this->assertEquals(
			[ 42, 'sort_b' ],
			$instance->fetchFromTableByTitle( 'foo', 0, '', '', $sortkey )
		);
	}

	public function testFindIdsByTitle() {

		$rows = [
			(object)[ 'smw_id' => 42 ],
			(object)[ 'smw_id' => 1001 ],
		];

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $rows ) );

		$instance = new EntityIdFinder(
			$this->connection,
			$this->propertyTableHashes,
			$this->idCacheManager
		);

		$sortkey = '';

		$this->assertEquals(
			[ 42, 1001 ],
			$instance->findIdsByTitle( 'foo', 0, '', '' )
		);
	}

}
