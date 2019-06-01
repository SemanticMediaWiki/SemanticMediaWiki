<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use SMW\MediaWiki\Specials\Admin\Supplement\TableStatisticsTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\TableStatisticsTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $outputFormatter;
	private $entityCache;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableStatisticsTaskHandler::class,
			new TableStatisticsTaskHandler( $this->outputFormatter, $this->entityCache )
		);
	}

	public function testGetHtml() {

		$instance = new TableStatisticsTaskHandler(
			$this->outputFormatter,
			$this->entityCache
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHandleRequest() {

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new TableStatisticsTaskHandler(
			$this->outputFormatter,
			$this->entityCache
		);

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

}
