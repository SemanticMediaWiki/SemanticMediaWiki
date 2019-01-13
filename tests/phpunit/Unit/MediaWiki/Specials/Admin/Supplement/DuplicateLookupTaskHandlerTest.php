<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use SMW\MediaWiki\Specials\Admin\Supplement\DuplicateLookupTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\DuplicateLookupTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateLookupTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DuplicateLookupTaskHandler::class,
			new DuplicateLookupTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$instance = new DuplicateLookupTaskHandler(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHandleRequest() {

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new DuplicateLookupTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

}
