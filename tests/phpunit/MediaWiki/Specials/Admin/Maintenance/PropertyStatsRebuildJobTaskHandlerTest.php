<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Maintenance;

use SMW\MediaWiki\Specials\Admin\Maintenance\PropertyStatsRebuildJobTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\PropertyStatsRebuildJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatsRebuildJobTaskHandlerTest extends \PHPUnit\Framework\TestCase {

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
			PropertyStatsRebuildJobTaskHandler::class,
			new PropertyStatsRebuildJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter )
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

		$instance = new PropertyStatsRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testHandleRequestOnNonPendingJob() {
		$this->jobQueue->expects( $this->once() )
			->method( 'hasPendingJob' )
			->with( 'smw.propertyStatisticsRebuild' )
			->willReturn( false );

		$propertyStatisticsRebuildJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsRebuildJob->expects( $this->once() )
			->method( 'insert' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->once() )
			->method( 'newByType' )
			->willReturn( $propertyStatisticsRebuildJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyStatsRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->isApiTask = false;
		$instance->setFeatureSet( SMW_ADM_PSTATS );
		$instance->handleRequest( $webRequest );
	}

	public function testHandleRequestOnPendingJob() {
		$this->jobQueue->expects( $this->once() )
			->method( 'hasPendingJob' )
			->with( 'smw.propertyStatisticsRebuild' )
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

		$instance = new PropertyStatsRebuildJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_PSTATS );
		$instance->handleRequest( $webRequest );
	}

}
