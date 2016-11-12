<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TransitionalDiffStore;
use Onoi\MessageReporter\MessageReporterFactory;

/**
 * @covers \SMW\SQLStore\TransitionalDiffStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TransitionalDiffStoreTest extends \PHPUnit_Framework_TestCase {

	private $cache;
	private $compositePropertyTableDiffIterator;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\TransitionalDiffStore',
			new TransitionalDiffStore( $this->cache )
		);
	}

	public function testGetSlot() {

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$this->assertContains(
			'smw:diff',
			$instance->getSlot( $this->compositePropertyTableDiffIterator )
		);
	}

	public function testCreateSlotWithDiff() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->will( $this->returnValue( array() ) );

		$this->compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( array( 'Foo' => 'Bar' ) ) );

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$this->assertContains(
			'smw:diff',
			$instance->createSlotFrom( $this->compositePropertyTableDiffIterator )
		);
	}

	public function testCreateSlotWithEmptyDiff() {

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$this->compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( array() ) );

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$this->assertNull(
			$instance->createSlotFrom( $this->compositePropertyTableDiffIterator )
		);
	}

	public function testDelete() {

		$this->cache->expects( $this->once() )
			->method( 'delete' );

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$instance->delete( 'foo' );
	}

	public function testNewTableChangeOpsFrom() {

		$res = array(
			'Foo' => array( 'Bar' ),
			'Foobar' => array( 'baz' )
		);

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( 'Foo:bar' ) )
			->will( $this->returnValue( $res ) );

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$this->assertContainsOnlyInstancesOf(
			'\SMW\SQLStore\ChangeOp\TableChangeOp',
			$instance->newTableChangeOpsFrom( 'Foo:bar' )
		);
	}

	public function testNewTableChangeOpsFromUnknownSlot() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' );

		$instance = new TransitionalDiffStore(
			$this->cache
		);

		$this->assertEmpty(
			$instance->newTableChangeOpsFrom( 'Foo:bar' )
		);
	}

}
