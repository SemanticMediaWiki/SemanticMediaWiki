<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\JobQueueLookup;

/**
 * @covers \SMW\MediaWiki\JobQueueLookup
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobQueueLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\JobQueueLookup',
			new JobQueueLookup( $database )
		);
	}

	public function testGetJobQueueStatisticsOnMockedStore() {

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->exactly( 3 ) )
			->method( 'estimateRowCount' )
			->with( $this->equalTo( 'job' ),
					$this->anything(),
					$this->anything(),
					$this->anything() )
			->will( $this->returnValue( 9999 ) );

		$instance = new JobQueueLookup( $database );

		$this->assertInternalType(
			'array',
			$instance->getStatistics()
		);
	}

}
