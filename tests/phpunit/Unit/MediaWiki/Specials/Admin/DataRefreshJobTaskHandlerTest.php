<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\DataRefreshJobTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\DataRefreshJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataRefreshJobTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $connection;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Admin\DataRefreshJobTaskHandler',
			new DataRefreshJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$methods = array(
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new DataRefreshJobTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testDoRefreshOn_Yes() {

		$refreshJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\RefreshJob' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->will( $this->returnValue( $refreshJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$jobQueueLookup = $this->getMockBuilder( '\SMW\MediaWiki\JobQueueLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueueLookup', $jobQueueLookup );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->will( $this->returnValue( 'yes' ) );

		$instance = new DataRefreshJobTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setEnabledFeatures( SMW_ADM_REFRESH );
		$instance->handleRequest( $webRequest );
	}

	public function testDoRefreshOn_Stop() {

		$jobQueueLookup = $this->getMockBuilder( '\SMW\MediaWiki\JobQueueLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueueLookup', $jobQueueLookup );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->atLeastOnce() )
			->method( 'getText' )
			->will( $this->returnValue( 'stop' ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new DataRefreshJobTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setEnabledFeatures( SMW_ADM_REFRESH );
		$instance->handleRequest( $webRequest );
	}

}
