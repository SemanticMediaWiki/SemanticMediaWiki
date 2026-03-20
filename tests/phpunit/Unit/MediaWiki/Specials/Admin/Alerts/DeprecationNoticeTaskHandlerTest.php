<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Alerts;

use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DeprecationNoticeTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $outputFormatter;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
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

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsString(

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

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsString(

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

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertStringContainsString(
			'<div class="smw-admin-deprecation">',
			$instance->getHtml()
		);
	}

}
