<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\JobQueueLookup;

/**
 * @covers \SMW\MediaWiki\JobQueueLookup
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JobQueueLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\JobQueueLookup',
			new JobQueueLookup( $connection )
		);
	}

	public function testEstimateJobCountFor() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'estimateRowCount' )
			->with( $this->stringContains( 'job' ),
				$this->anything(),
				$this->equalTo( array( 'job_cmd' => 'Foo' ) ),
				$this->anything() )
			->will( $this->returnValue( 1 ) );

		$instance = new JobQueueLookup( $connection );

		$this->assertInternalType(
			'integer',
			$instance->estimateJobCountFor( 'Foo' )
		);
	}

}
