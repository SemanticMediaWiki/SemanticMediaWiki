<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\InsertJobTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\InsertJobTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InsertJobTaskTest extends \PHPUnit_Framework_TestCase {

	private $jobFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new InsertJobTask( $this->jobFactory );

		$this->assertInstanceOf(
			InsertJobTask::class,
			$instance
		);
	}

	public function testProcess() {

		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$nullJob->expects( $this->atLeastOnce() )
			->method( 'insert' );

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->with(
				$this->equalTo( 'Foobar' ),
				$this->anything(),
				$this->anything() )
			->will( $this->returnValue( $nullJob ) );

		$instance = new InsertJobTask(
			$this->jobFactory
		);

		$instance->process( [ 'subject' => 'Foo#0##', 'job' => 'Foobar' ]);
	}

}
