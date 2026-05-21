<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\CacheStatisticsListTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\CacheStatisticsListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CacheStatisticsListTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturn( '' );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CacheStatisticsListTaskHandler::class,
			new CacheStatisticsListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {
		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {
		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertTrue(
			$instance->isTaskFor( 'stats/cache' )
		);
	}

	public function testHandleRequest() {
		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

}
