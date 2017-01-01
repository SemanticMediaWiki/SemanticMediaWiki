<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\SupplementaryLinksWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupplementaryLinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SupplementaryLinksWidgetTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\MediaWiki\Specials\Admin\SupplementaryLinksWidget',
			new SupplementaryLinksWidget( $this->outputFormatter )
		);
	}

	public function testGetForm() {

		$instance = new SupplementaryLinksWidget(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getForm()
		);
	}

}
