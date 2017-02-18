<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\PropertyStatsRebuildJobTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\PropertyStatsRebuildJobTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatsRebuildJobTaskHandlerTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\MediaWiki\Specials\Admin\PropertyStatsRebuildJobTaskHandler',
			new PropertyStatsRebuildJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter )
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

		$instance = new PropertyStatsRebuildJobTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->getHtml();
	}

	public function testHandleRequest() {

		$propertyStatisticsRebuildJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsRebuildJob->expects( $this->once() )
			->method( 'insert' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->once() )
			->method( 'newByType' )
			->will( $this->returnValue( $propertyStatisticsRebuildJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyStatsRebuildJobTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->setEnabledFeatures( SMW_ADM_PSTATS );
		$instance->handleRequest( $webRequest );
	}

}
