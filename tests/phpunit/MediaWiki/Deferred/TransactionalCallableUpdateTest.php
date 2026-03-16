<?php

namespace SMW\Tests\MediaWiki\Deferred;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Deferred\TransactionalCallableUpdate
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TransactionalCallableUpdateTest extends TestCase {

	private $testEnvironment;
	private $spyLogger;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->clearPendingDeferredUpdates();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$callback = static function () {
			return null;
		};

		$this->assertInstanceOf(
			TransactionalCallableUpdate::class,
			new TransactionalCallableUpdate( $callback, $this->connection )
		);

		$this->assertInstanceOf(
			TransactionalCallableUpdate::class,
			TransactionalCallableUpdate::newUpdate( $callback, $this->connection )
		);
	}

	public function testUpdate() {
		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnEmptyCallback() {
		$instance = new TransactionalCallableUpdate(
			null,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertStringContainsString(
			'Empty callback',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testUpdateOnLateCallback() {
		$instance = new TransactionalCallableUpdate(
			null,
			$this->connection
		);

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance->setCallback( $callback );

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertStringContainsString(
			'Added',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testWaitableUpdate() {
		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance->releasePendingUpdates();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateWithDisabledDeferredUpdate() {
		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->enabledDeferredUpdate( false );
		$instance->pushUpdate();
	}

	public function testOrigin() {
		$callback = static function () {
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setOrigin( 'Foo' );

		$this->assertStringContainsString(
			'Foo',
			$instance->getOrigin()
		);
	}

	public function testFilterDuplicateQueueEntryByFingerprint() {
		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance = new TransactionalCallableUpdate(
			$callback,
			$this->connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnTransactionIdle() {
		$callback = static function ( $callback ) {
			return call_user_func( $callback );
		};

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( $callback );

		$this->testEnvironment->clearPendingDeferredUpdates();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->waitOnTransactionIdle();
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testCommitWithTransactionTicketOnDeferrableUpdate() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getEmptyTransactionTicket' );

		$this->testEnvironment->clearPendingDeferredUpdates();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->isDeferrableUpdate( true );
		$instance->commitWithTransactionTicket();
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testCommitWithTransactionTicketOnNonDeferrableUpdate() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'getEmptyTransactionTicket' );

		$this->testEnvironment->clearPendingDeferredUpdates();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->isDeferrableUpdate( false );
		$instance->commitWithTransactionTicket();
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testCancelOnRollback() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'getEmptyTransactionTicket' );

		$this->testEnvironment->clearPendingDeferredUpdates();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->never() )
			->method( 'doTest' );

		$callback = static function () use ( $test ) {
			$test->doTest();
		};

		$instance = new TransactionalCallableUpdate(
			$callback,
			$connection
		);

		$instance->setLogger( $this->spyLogger );

		$instance->isDeferrableUpdate( false );
		$instance->commitWithTransactionTicket();

		// #3765
		$instance->cancelOnRollback( Database::TRIGGER_ROLLBACK );

		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

}
