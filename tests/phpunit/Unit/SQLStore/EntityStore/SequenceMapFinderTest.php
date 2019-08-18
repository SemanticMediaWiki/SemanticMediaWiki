<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\SequenceMapFinder;

/**
 * @covers \SMW\SQLStore\EntityStore\SequenceMapFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class SequenceMapFinderTest extends \PHPUnit_Framework_TestCase {

	private $cache;
	private $idCacheManager;
	private $conection;

	protected function setUp() {

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
				$this->equalTo( $row ),
				$this->equalTo( [ 'smw_id' ] ),
				$this->equalTo( $row ) );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->setMap( 42, [ 'Foo' ] );
	}

	public function testFindMapById() {

		$map = \SMW\Utils\HmacSerializer::compress( [ 'Foo' ] );

		$row = [
			'smw_id' => 1001,
			'smw_seqmap' => $map
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->will( $this->returnArgument( 0 ) );

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

		$map = \SMW\Utils\HmacSerializer::compress( [ 'Foo' ] );

		$row = [
			'smw_id' => 1001,
			'smw_seqmap' => $map
		];

		$this->cache->expects( $this->at( 0 ) )
			->method( 'save' )
			->with(
				$this->equalTo( 1001 ),
				$this->equalTo( [ 'Foo' ] ) );

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'smw_id', 'smw_seqmap'] ),
				$this->equalTo( [ 'smw_id' => [ 42, 1001 ] ] ) )
			->will( $this->returnValue( [ (object)$row ] ) );

		$this->connection->expects( $this->once() )
			->method( 'unescape_bytea' )
			->will( $this->returnArgument( 0 ) );

		$instance = new SequenceMapFinder(
			$this->connection,
			$this->idCacheManager
		);

		$instance->prefetchSequenceMap( [ 42, 1001 ] );
	}

}
