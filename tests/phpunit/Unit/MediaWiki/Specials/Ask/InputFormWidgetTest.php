<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\InputFormWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\InputFormWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class InputFormWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Ask\InputFormWidget',
			new InputFormWidget()
		);
	}

	public function testCreateEmbeddedCodeLinkElement() {

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createEmbeddedCodeLinkElement()
		);
	}

	public function testCreateEmbeddedCodeElement() {

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createEmbeddedCodeElement( 'Foo' )
		);
	}

	public function testCreateFindResultLinkElementHide() {

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createFindResultLinkElement( true )
		);
	}

	public function testCreateFindResultLinkElementShow() {

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createFindResultLinkElement( false )
		);
	}

	public function testCreateShowHideLinkElement() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createShowHideLinkElement( $title )
		);
	}

	public function testCreateClipboardLinkElement() {

		$infolink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new InputFormWidget();

		$this->assertInternalType(
			'string',
			$instance->createClipboardLinkElement( $infolink )
		);
	}

}
