<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationClassUpdateJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $store );
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
			ChangePropagationClassUpdateJob::class,
			new ChangePropagationClassUpdateJob( $title )
		);
	}

	/**
	 * @dataProvider jobProvider
	 */
	public function testRun( $subject, $parameters ) {

		$instance = new ChangePropagationClassUpdateJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function jobProvider() {

		$provider[] = [
			DIWikiPage::newFromText( __METHOD__ ),
			[]
		];

		return $provider;
	}

}
