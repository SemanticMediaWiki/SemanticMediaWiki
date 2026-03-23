<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Supplement;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ConfigurationListTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturn( '' );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConfigurationListTaskHandler::class,
			new ConfigurationListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {
		$instance = new ConfigurationListTaskHandler(
			$this->outputFormatter
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testHandleRequest() {
		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new ConfigurationListTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

}
