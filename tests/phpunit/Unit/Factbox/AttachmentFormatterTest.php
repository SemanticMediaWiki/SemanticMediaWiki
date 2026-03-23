<?php

namespace SMW\Tests\Unit\Factbox;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Factbox\AttachmentFormatter;
use SMW\Store;

/**
 * @covers \SMW\Factbox\AttachmentFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AttachmentFormatterTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AttachmentFormatter::class,
			new AttachmentFormatter( $this->store )
		);
	}

	public function testBuildHTML_OnEmptyAttachments() {
		$instance = new AttachmentFormatter(
			$this->store
		);

		$this->assertIsString(

			$instance->buildHTML( [] )
		);
	}

	public function testBuildHTML_OnAttachments() {
		$item = WikiPage::newFromText( 'Foo', NS_FILE );

		$instance = new AttachmentFormatter(
			$this->store
		);

		$this->assertIsString(

			$instance->buildHTML( [ $item ] )
		);
	}

}
