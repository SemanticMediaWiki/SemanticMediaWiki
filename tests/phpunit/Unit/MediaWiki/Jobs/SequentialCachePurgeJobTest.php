<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\SequentialCachePurgeJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\SequentialCachePurgeJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SequentialCachePurgeJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $transitionalDiffStore;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->transitionalDiffStore = $this->getMockBuilder( '\SMW\SQLStore\TransitionalDiffStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'TransitionalDiffStore', $this->transitionalDiffStore );
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
			'SMW\MediaWiki\Jobs\SequentialCachePurgeJob',
			new SequentialCachePurgeJob( $title )
		);
	}

	public function testRun() {

		$parameters = array(
			'slot:id' => 42
		);

		$this->transitionalDiffStore->expects( $this->once() )
			->method( 'delete' );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new SequentialCachePurgeJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

}
