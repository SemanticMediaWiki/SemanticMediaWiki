<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeprecationNoticeTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $outputFormatter;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
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

		$this->assertContains(
			'<div class="smw-admin-deprecation">',
			$instance->getHtml()
		);
	}

}
