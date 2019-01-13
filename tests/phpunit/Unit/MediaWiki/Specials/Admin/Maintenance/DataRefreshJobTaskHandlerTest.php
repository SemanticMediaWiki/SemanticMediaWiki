<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Maintenance;

use SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataRefreshJobTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $outputFormatter;
	private $jobQueue;

	protected function setUp() {
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

	protected function tearDown() {
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
				->will( $this->returnSelf() );
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

		$refreshJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\RefreshJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'hasPendingJob' )
			->with( $this->equalTo( 'SMW\RefreshJob' ) )
			->will( $this->returnValue( true ) );

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'pop' )
			->with( $this->equalTo( 'SMW\RefreshJob' ) )
			->will( $this->returnValue( false ) );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->will( $this->returnValue( $refreshJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->will( $this->returnValue( 'yes' ) );

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
			->with( $this->equalTo( 'SMW\RefreshJob' ) );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->will( $this->returnValue( 'stop' ) );

		$instance = new DataRefreshJobTaskHandler(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setFeatureSet( SMW_ADM_REFRESH );
		$instance->handleRequest( $webRequest );
	}

}
