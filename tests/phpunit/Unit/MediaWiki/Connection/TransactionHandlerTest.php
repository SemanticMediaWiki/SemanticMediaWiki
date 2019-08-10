<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\TransactionHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\TransactionHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TransactionHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $loadBalancerFactory;
	private $transactionProfiler;

	protected function setUp() {

		if ( interface_exists( '\Wikimedia\Rdbms\ILBFactory' ) ) {
			$this->loadBalancerFactory = $this->getMockBuilder( '\Wikimedia\Rdbms\ILBFactory' )
				->disableOriginalConstructor()
				->getMock();
		} else {
			$this->loadBalancerFactory = $this->getMockBuilder( '\stdClass' )
				->disableOriginalConstructor()
				->setMethods( [ 'getEmptyTransactionTicket', 'hasMasterChanges', 'commitAndWaitForReplication' ] )
				->getMock();
		}

		$this->transactionProfiler = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'setSilenced' ] )
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TransactionHandler::class,
			new TransactionHandler( $this->loadBalancerFactory )
		);
	}

	public function testCanConstruct_ThrowsException() {

		$this->setExpectedException( '\RuntimeException' );
		new TransactionHandler( 'Foo' );
	}

	public function testGetEmptyTransactionTicket() {

		$this->loadBalancerFactory->expects( $this->once() )
			->method( 'hasMasterChanges' )
			->will( $this->returnValue( false ) );

		$this->loadBalancerFactory->expects( $this->once() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->getEmptyTransactionTicket( __METHOD__ );
	}

	public function testGetEmptyTransactionTicketOnMasterChanges() {

		$this->loadBalancerFactory->expects( $this->once() )
			->method( 'hasMasterChanges' )
			->will( $this->returnValue( true ) );

		$this->loadBalancerFactory->expects( $this->never() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$this->assertNull(
			$instance->getEmptyTransactionTicket( __METHOD__ )
		);
	}

	public function testCommitAndWaitForReplication() {

		$this->loadBalancerFactory->expects( $this->once() )
			->method( 'commitAndWaitForReplication' );

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->commitAndWaitForReplication( __METHOD__, 123 );
	}

	public function testMarkSectionTransaction() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->markSectionTransaction( __METHOD__ );

		$this->assertTrue(
			$instance->inSectionTransaction( __METHOD__ )
		);
	}

	public function testMarkDetachSectionTransaction() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->markSectionTransaction( __METHOD__ );

		$this->assertTrue(
			$instance->inSectionTransaction( __METHOD__ )
		);

		$this->assertTrue(
			$instance->hasActiveSectionTransaction()
		);

		$instance->detachSectionTransaction( __METHOD__ );

		$this->assertFalse(
			$instance->inSectionTransaction( __METHOD__ )
		);

		$this->assertFalse(
			$instance->hasActiveSectionTransaction()
		);
	}

	public function testMarkSectionTransactionWithAnotherActive_ThrowsException() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->markSectionTransaction( __METHOD__ );

		$this->setExpectedException( '\RuntimeException' );
		$instance->markSectionTransaction( 'Foo' );
	}

	public function testDetachSectionTransactionOnNonActiveSection_ThrowsException() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->detachSectionTransaction( __METHOD__ );
	}

	public function testMuteTransactionProfiler() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->setTransactionProfiler(
			$this->transactionProfiler
		);

		$this->transactionProfiler->expects( $this->once() )
			->method( 'setSilenced' )
			->will( $this->returnValue( true ) );

		$instance->muteTransactionProfiler( true );

		// Second time
		$instance->muteTransactionProfiler( true );
	}

	public function testUnmuteTransactionProfiler() {

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->setTransactionProfiler(
			$this->transactionProfiler
		);

		$this->transactionProfiler->expects( $this->exactly( 2 ) )
			->method( 'setSilenced' )
			->will( $this->returnValue( true ) );

		$instance->muteTransactionProfiler( true );
		$instance->muteTransactionProfiler( false );
	}

}
