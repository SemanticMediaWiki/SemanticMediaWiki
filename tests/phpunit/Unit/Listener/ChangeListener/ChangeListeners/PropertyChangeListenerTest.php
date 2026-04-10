<?php

namespace SMW\Tests\Unit\Listener\ChangeListener\ChangeListeners;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Property;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\HookDispatcher;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListenerTest extends TestCase {

	private $store;
	private $logger;
	private $hookDispatcher;
	private $property;
	private $changeRecord;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( LoggerInterface::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
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
		$property = new Property( 'Foo' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
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
		$property = new Property( 'Foo' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
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
		$connection = $this->getMockBuilder( Database::class )
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
