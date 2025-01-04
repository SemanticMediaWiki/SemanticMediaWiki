<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\AlertsTaskHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\AlertsTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AlertsTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AlertsTaskHandler::class,
			new AlertsTaskHandler( $this->outputFormatter, [] )
		);
	}

	public function testGetHtml() {
		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getName', 'getHtml' ] )
			->getMockForAbstractClass();

		$taskHandler->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'foo' );

		$taskHandler->expects( $this->once() )
			->method( 'getHtml' )
			->willReturn( 'bar' );

		$instance = new AlertsTaskHandler(
			$this->outputFormatter,
			[
				$taskHandler
			]
		);

		$this->assertContains(
			'<section id="tab-content-foo">bar</section>',
			$instance->getHtml()
		);
	}

}
