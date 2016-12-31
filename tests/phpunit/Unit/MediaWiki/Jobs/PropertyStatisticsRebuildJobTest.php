<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;

/**
 * @covers \SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsRebuildJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

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
			'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			new PropertyStatisticsRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new PropertyStatisticsRebuildJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			array()
		);

		return $provider;
	}

}
