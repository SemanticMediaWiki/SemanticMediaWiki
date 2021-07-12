<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\Tests\Utils\Mock\MockTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\SidebarBeforeOutput
 *
 * @license GNU GPL v2+
 */
class SidebarBeforeOutputTest extends \PHPUnit_Framework_TestCase {

	private $namespaceExaminer;

	protected function setUp() : void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SidebarBeforeOutput::class,
			new SidebarBeforeOutput( $this->namespaceExaminer )
		);
	}

}
