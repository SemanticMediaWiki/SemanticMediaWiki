<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\SearchTableUpdateJob;

/**
 * @covers \SMW\MediaWiki\Jobs\SearchTableUpdateJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableUpdateJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->registerObject(
			'Store',
			$this->getMockBuilder( '\SMW\SQLStore\SQLStore' )->getMockForAbstractClass()
		);
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
			'SMW\MediaWiki\Jobs\SearchTableUpdateJob',
			new SearchTableUpdateJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new SearchTableUpdateJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			'diff' => array( 1, 2 )
		);

		return $provider;
	}

}
