<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleDelete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDeleteTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $jobFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgEnableUpdateJobs' => false,
				'smwgEnabledDeferredUpdate' => false
			]
		);

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ArticleDelete( $store );

		$this->assertInstanceOf(
			ArticleDelete::class,
			$instance
		);
	}

	public function testProcess() {

		$updateDispatcherJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateDispatcherJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateDispatcherJob' )
			->will( $this->returnValue( $updateDispatcherJob ) );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'deleteSubject' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array( new DIProperty( 'Foo' ) ) ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$instance = new ArticleDelete(
			$store
		);

		$this->assertTrue(
			$instance->process( $wikiPage )
		);
	}

}
