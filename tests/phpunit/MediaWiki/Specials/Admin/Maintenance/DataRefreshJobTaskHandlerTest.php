<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Maintenance;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataRefreshJobTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $outputFormatter;
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

		$this->jobQueue = $this->getMockBuilder( JobQueue::class )
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
			DataRefreshJobTaskHandler::class,
			new DataRefreshJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter )
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

		$instance = new DataRefreshJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testDoRefreshOn_Yes() {
		$refreshJob = $this->getMockBuilder( RefreshJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'hasPendingJob' )
			->with( 'smw.refresh' )
			->willReturn( true );

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'pop' )
			->with( 'smw.refresh' )
			->willReturn( false );

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->willReturn( $refreshJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->willReturn( 'yes' );

		$instance = new DataRefreshJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_REFRESH );
		$instance->handleRequest( $webRequest );
	}

	public function testDoRefreshOn_Stop() {
		$this->jobQueue->expects( $this->once() )
			->method( 'delete' )
			->with( 'smw.refresh' );

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->willReturn( 'stop' );

		$instance = new DataRefreshJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_REFRESH );
		$instance->handleRequest( $webRequest );
	}

}
