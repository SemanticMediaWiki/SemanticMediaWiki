<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Maintenance;

use SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $outputFormatter;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FulltextSearchTableRebuildJobTaskHandler::class,
			new FulltextSearchTableRebuildJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetHtml() {
		$methods = [
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton'
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new FulltextSearchTableRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testHandleRequestOnNonPendingJob() {
		$this->jobQueue->expects( $this->once() )
			->method( 'hasPendingJob' )
			->with( 'smw.fulltextSearchTableRebuild' )
			->willReturn( false );

		$fulltextSearchTableRebuildJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob' )
			->disableOriginalConstructor()
			->getMock();

		$fulltextSearchTableRebuildJob->expects( $this->once() )
			->method( 'insert' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->once() )
			->method( 'newByType' )
			->willReturn( $fulltextSearchTableRebuildJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FulltextSearchTableRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->isApiTask = false;
		$instance->setFeatureSet( SMW_ADM_FULLT );
		$instance->handleRequest( $webRequest );
	}

	public function testHandleRequestOnPendingJob() {
		$this->jobQueue->expects( $this->once() )
			->method( 'hasPendingJob' )
			->with( 'smw.fulltextSearchTableRebuild' )
			->willReturn( true );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->never() )
			->method( 'newByType' );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FulltextSearchTableRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_FULLT );
		$instance->handleRequest( $webRequest );
	}

}
