<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeprecationNoticeTaskHandlerTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler',
			new DeprecationNoticeTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$instance = new DeprecationNoticeTaskHandler(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testGetHtmlWithFakeDetection() {

		$GLOBALS['deprecationNoticeFoo'] = 'Foo';
		$GLOBALS['deprecationNoticeFoobar'] = 'Foo';
		$GLOBALS['deprecationNoticeFooFoo'] = 'Foo';

		$deprecationNotice = array(
			'notice' => array(
				'deprecationNoticeFoo' => '...'
			),
			'replacement' => array(
				'deprecationNoticeFoobar' => '...'
			),
			'removal' => array(
				'deprecationNoticeFooFoo' => '...'
			)
		);

		$instance = new DeprecationNoticeTaskHandler(
			$this->outputFormatter,
			$deprecationNotice
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

}
