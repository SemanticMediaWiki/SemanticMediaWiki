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
	private $changeOp;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TempChangeOpStore::class,
			new TempChangeOpStore( $this->cache )
		);
	}

	public function testGetSlot() {

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertContains(
			'smw:diff',
			$instance->getSlot( $this->changeOp )
		);
	}

	public function testCreateSlotWithDiff() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->will( $this->returnValue( array() ) );

		$this->changeOp->expects( $this->once() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( array( 'Foo' => 'Bar' ) ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertContains(
			'smw:diff',
			$instance->createSlotFrom( $this->changeOp )
		);
	}

	public function testCreateSlotWithEmptyDiff() {

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$this->changeOp->expects( $this->once() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( array() ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertNull(
			$instance->createSlotFrom( $this->changeOp )
		);
	}

	public function testNewChangeOp() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( serialize( $this->changeOp ) ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$instance->newChangeOp( 'foo' )
		);
	}

	public function testNewChangeOpOnInvalidSlot() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new TempChangeOpStore(
			$this->cache
		);

		$this->assertNull(
			$instance->newChangeOp( 'foo' )
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
