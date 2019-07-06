<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\SilenceableTransactionProfiler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\SilenceableTransactionProfiler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SilenceableTransactionProfilerTest extends \PHPUnit_Framework_TestCase {

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
			SilenceableTransactionProfiler::class,
			new SilenceableTransactionProfiler( $this->transactionProfiler )
		);
	}

	public function testSetSilenced_Enabled() {

		$instance = new SilenceableTransactionProfiler(
			$this->transactionProfiler
		);

		$instance->silenceTransactionProfiler();

		$this->transactionProfiler->expects( $this->once() )
			->method( 'setSilenced' );

		$instance->setSilenced( true );
	}

	public function testSetSilenced_NotEnabled() {

		$instance = new SilenceableTransactionProfiler(
			$this->transactionProfiler
		);

		$this->transactionProfiler->expects( $this->never() )
			->method( 'setSilenced' );

		$instance->setSilenced( true );
	}

}
