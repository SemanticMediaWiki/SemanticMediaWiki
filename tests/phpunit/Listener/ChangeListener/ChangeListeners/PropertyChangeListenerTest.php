<?php

namespace SMW\Tests\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\DIProperty;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListenerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $logger;
	private $hookDispatcher;
	private $property;
	private $changeRecord;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyChangeListener::class,
			new PropertyChangeListener( $this->store )
		);
	}

	public function testCanTrigger() {
		$property = new DIProperty( 'Foo' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$instance = new PropertyChangeListener( $this->store );
		$instance->addListenerCallback( $property, [ $this, 'observeChange' ] );

		$this->assertFalse(
			$instance->canTrigger( 'bar' )
		);

		$this->assertTrue(
			$instance->canTrigger( 'Foo' )
		);
	}

	public function testRecordAndMatch() {
		$property = new DIProperty( 'Foo' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$instance = new PropertyChangeListener( $this->store );
		$instance->addListenerCallback( $property, [ $this, 'observeChange' ] );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->loadListeners();

		$instance->setLogger(
			$this->logger
		);

		$instance->recordChange( 42, [ 'row' => [ 's_id' => 1000, 'o_hash' => 'foobar' ] ] );
		$instance->triggerChangeListeners();

		$this->assertEquals(
			$property,
			$this->property
		);

		$this->assertEquals(
			'foobar',
			$this->changeRecord->get( 0 )->get( 'row.o_hash' )
		);
	}

	public function testRunChangeListeners() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyChangeListener(
			$this->store
		);

		$instance->runChangeListeners();
	}

	public function testLoadListeners() {
		$this->hookDispatcher->expects( $this->once() )
			->method( 'onRegisterPropertyChangeListeners' );

		$instance = new PropertyChangeListener( $this->store );

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->loadListeners();
	}

	public function testMissedLoadListenersThrowsException() {
		$instance = new PropertyChangeListener( $this->store );

		$this->expectException( '\RuntimeException' );
		$instance->recordChange( 42, [] );
	}

	public function observeChange( $property, $record ) {
		$this->property = $property;
		$this->changeRecord = $record;
	}

}
