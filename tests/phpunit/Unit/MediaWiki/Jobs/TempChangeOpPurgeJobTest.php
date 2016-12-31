<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\TempChangeOpPurgeJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\TempChangeOpPurgeJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TempChangeOpPurgeJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $tempChangeOpStore;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->tempChangeOpStore = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\TempChangeOpStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'TempChangeOpStore', $this->tempChangeOpStore );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\TempChangeOpPurgeJob',
			new TempChangeOpPurgeJob( $title )
		);
	}

	public function testRun() {

		$parameters = array(
			'slot:id' => 42
		);

		$this->tempChangeOpStore->expects( $this->once() )
			->method( 'delete' );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new TempChangeOpPurgeJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

}
