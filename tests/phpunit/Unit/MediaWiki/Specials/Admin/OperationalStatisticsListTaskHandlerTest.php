<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\OperationalStatisticsListTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\OperationalStatisticsListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OperationalStatisticsListTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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
			'\SMW\MediaWiki\Specials\Admin\OperationalStatisticsListTaskHandler',
			new OperationalStatisticsListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHandleRequest() {

		$semanticStatistics = array(
			'PROPUSES' => 0,
			'ERRORUSES' => 0,
			'USEDPROPS' => 0,
			'TOTALPROPS' => 0,
			'OWNPAGE' => 0,
			'DECLPROPS' => 0,
			'DELETECOUNT' => 0,
			'SUBOBJECTS' => 0,
			'QUERY' => 0,
			'CONCEPTS' => 0
		);

		$this->store->expects( $this->once() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $semanticStatistics ) );

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

}
