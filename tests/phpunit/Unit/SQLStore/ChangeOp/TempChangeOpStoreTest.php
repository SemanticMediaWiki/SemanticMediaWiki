<?php

namespace SMW\Tests\SQLStore\ChangeOp;

use SMW\SQLStore\ChangeOp\TempChangeOpStore;
use Onoi\MessageReporter\MessageReporterFactory;

/**
 * @covers \SMW\SQLStore\ChangeOp\TempChangeOpStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TempChangeOpStoreTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\ChangeOp\TempChangeOpStore',
			new TempChangeOpStore( $this->cache )
		);
	}

	public function testGetSlot() {

		$instance = new TempChangeOpStore(
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

		$instance = new TempChangeOpStore(
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

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertNull(
			$instance->createSlotFrom( $this->compositePropertyTableDiffIterator )
		);
	}

	public function testNewCompositePropertyTableDiffIterator() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( serialize( $this->compositePropertyTableDiffIterator ) ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\CompositePropertyTableDiffIterator',
			$instance->newCompositePropertyTableDiffIterator( 'foo' )
		);
	}

	public function testNewCompositePropertyTableDiffIteratorOnInvalidSlot() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertNull(
			$instance->newCompositePropertyTableDiffIterator( 'foo' )
		);
	}

	public function testDelete() {

		$this->cache->expects( $this->once() )
			->method( 'delete' );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$instance->delete( 'foo' );
	}

}
