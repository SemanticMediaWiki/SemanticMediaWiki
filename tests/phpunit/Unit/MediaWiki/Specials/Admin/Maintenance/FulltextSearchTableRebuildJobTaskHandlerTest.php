<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Maintenance;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $outputFormatter;
	private $jobFactory;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->htmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FulltextSearchTableRebuildJobTaskHandler::class,
			new FulltextSearchTableRebuildJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter, $this->jobFactory, $this->jobQueue )
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
			$this->outputFormatter,
			$this->jobFactory,
			$this->jobQueue
		);

		$instance->getHtml();
	}

	public function testHandleRequestOnNonPendingJob() {
		$this->jobQueue->expects( $this->once() )
			->method( 'hasPendingJob' )
			->with( 'smw.fulltextSearchTableRebuild' )
			->willReturn( false );

		$fulltextSearchTableRebuildJob = $this->getMockBuilder( FulltextSearchTableRebuildJob::class )
			->disableOriginalConstructor()
			->getMock();

		$fulltextSearchTableRebuildJob->expects( $this->once() )
			->method( 'insert' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newByType' )
			->willReturn( $fulltextSearchTableRebuildJob );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FulltextSearchTableRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter,
			$this->jobFactory,
			$this->jobQueue
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

		$this->jobFactory->expects( $this->never() )
			->method( 'newByType' );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FulltextSearchTableRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter,
			$this->jobFactory,
			$this->jobQueue
		);

		$instance->setFeatureSet( SMW_ADM_FULLT );
		$instance->handleRequest( $webRequest );
	}

}
