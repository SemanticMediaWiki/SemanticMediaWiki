<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\TransactionHandler;
use SMW\Tests\PHPUnitCompat;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @covers \SMW\MediaWiki\Connection\TransactionHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TransactionHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $loadBalancerFactory;
	private $transactionProfiler;

	protected function setUp(): void {
		$this->loadBalancerFactory = $this->createMock( ILBFactory::class );

		$this->transactionProfiler = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'setSilenced' ] )
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TransactionHandler::class,
			new TransactionHandler( $this->loadBalancerFactory )
		);
	}

	public function testGetEmptyTransactionTicket() {
		$this->loadBalancerFactory->expects( $this->once() )
			->method( self::getHasPrimaryChangesMethod() )
			->willReturn( false );

		$this->loadBalancerFactory->expects( $this->once() )
			->method( 'getEmptyTransactionTicket' );

		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$instance->getEmptyTransactionTicket( __METHOD__ );
	}

	public function testGetEmptyTransactionTicketOnMasterChanges() {
		$this->loadBalancerFactory->expects( $this->once() )
			->method( self::getHasPrimaryChangesMethod() )
			->willReturn( true );

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

		$this->expectException( '\RuntimeException' );
		$instance->markSectionTransaction( 'Foo' );
	}

	public function testDetachSectionTransactionOnNonActiveSection_ThrowsException() {
		$instance = new TransactionHandler(
			$this->loadBalancerFactory
		);

		$this->expectException( '\RuntimeException' );
		$instance->detachSectionTransaction( __METHOD__ );
	}

	/**
	 * Get the appropriate `hasMaster/PrimaryChanges` method to mock for the `ILBFactory` interface.
	 * @return string
	 */
	private static function getHasPrimaryChangesMethod(): string {
		return method_exists( ILBFactory::class, 'hasPrimaryChanges' ) ? 'hasPrimaryChanges' : 'hasMasterChanges';
	}
}
