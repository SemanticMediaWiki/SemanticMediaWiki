<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Task;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Task
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TaskTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new Task(
			$this->apiFactory->newApiMain( array() ),
			'smwtask'
		);

		$this->assertInstanceOf(
			Task::class,
			$instance
		);
	}

	public function testHandleQueryRefTask() {

		$updateJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateJob' )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->atLeastOnce() )
			->method( 'run' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->will( $this->returnValue( $updateJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$instance = new Task(
			$this->apiFactory->newApiMain( array(
					'action'     => 'smwtask',
					'subject'    => 'Foo#0##',
					'taskType'   => 'queryref',
					'taskParams' => json_encode( [ 'Bar' ] ),
					'token'      => 'foo'
				)
			),
			'smwtask'
		);

		$instance->execute();
	}

}
