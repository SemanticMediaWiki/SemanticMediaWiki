<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\TransactionProfiler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\TransactionProfiler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TransactionProfilerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

		$this->transactionProfiler = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'setSilenced' ] )
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TransactionProfiler::class,
			new TransactionProfiler( $this->transactionProfiler )
		);
	}

	public function testSetSilenced_Enabled() {

		$instance = new TransactionProfiler(
			$this->transactionProfiler
		);

		$instance->silenceTransactionProfiler();

		$this->transactionProfiler->expects( $this->once() )
			->method( 'setSilenced' );

		$instance->setSilenced( true );
	}

	public function testSetSilenced_NotEnabled() {

		$instance = new TransactionProfiler(
			$this->transactionProfiler
		);

		$this->transactionProfiler->expects( $this->never() )
			->method( 'setSilenced' );

		$instance->setSilenced( true );
	}

}
