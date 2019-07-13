<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\AttachmentFormatter;

/**
 * @covers \SMW\Factbox\AttachmentFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AttachmentFormatterTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
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

		$this->assertInternalType(
			'string',
			$instance->buildHTML( [] )
		);
	}

	public function testBuildHTML_OnAttachments() {

		$item = \SMW\DIWikiPage::newFromText( 'Foo', NS_FILE );

		$instance = new AttachmentFormatter(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->buildHTML( [ $item ] )
		);
	}

}
