<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\DeprecationNoticeTaskHandler;
use SMW\Tests\TestEnvironment;

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
			DeprecationNoticeTaskHandler::class,
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

		$deprecationNotice['smw'] = [
			'notice' => [
				'deprecationNoticeFoo' => '...',
				'options' => [
					'deprecationNoticeFoo' => [
						'Foo',
						'Bar'
					]
				]
			],
			'replacement' => [
				'deprecationNoticeFoobar' => '...',
				'options' => [
					'deprecationNoticeFoobar' => [
						'Foo',
						'Bar'
					]
				]
			],
			'removal' => [
				'deprecationNoticeFooFoo' => '...'
			]
		];

		$instance = new DeprecationNoticeTaskHandler(
			$this->outputFormatter,
			$deprecationNotice
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testGetHtmlWithFakeDetectionArray() {

		$GLOBALS['deprecationNoticeFoo'] = [ 'Bar' => false ];
		$GLOBALS['deprecationNoticeFoobar'] = 'Foo';
		$GLOBALS['deprecationNoticeFooFoo'] = 'Foo';

		$deprecationNotice['smw'] = [
			'notice' => [
				'deprecationNoticeFoo' => '...',
				'options' => [
					'deprecationNoticeFoo' => [
						'Foo',
						'Bar'
					]
				]
			],
			'replacement' => [
				'deprecationNoticeFoobar' => '...',
				'options' => [
					'deprecationNoticeFoobar' => [
						'Foo',
						'Bar'
					]
				]
			],
			'removal' => [
				'deprecationNoticeFooFoo' => '...'
			]
		];

		$instance = new DeprecationNoticeTaskHandler(
			$this->outputFormatter,
			$deprecationNotice
		);

		$this->assertContains(
			'<div class="smw-admin-deprecation">',
			$instance->getHtml()
		);
	}

}
