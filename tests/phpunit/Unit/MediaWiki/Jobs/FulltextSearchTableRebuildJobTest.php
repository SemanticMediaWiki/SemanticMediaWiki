<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTest extends \PHPUnit_Framework_TestCase {

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
			'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			new FulltextSearchTableRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new FulltextSearchTableRebuildJob(
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

		$provider[] = array(
			array( 'table' => 'Foo' )
		);

		$provider[] = array(
			array( 'mode' => 'full' )
		);

		return $provider;
	}

}
