<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\JobQueueLookup;

/**
 * @covers \SMW\MediaWiki\JobQueueLookup
 * @group semantic-mediawiki
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

	public function testSelectJobRow() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->with( $this->equalTo( 'job' ),
					$this->anything(),
					$this->anything(),
					$this->anything() )
			->will( $this->returnValue( false ) );

		$instance = new JobQueueLookup( $connection );

		$this->assertFalse(
			$instance->selectJobRowFor( 'Foo' )
		);
	}

}
