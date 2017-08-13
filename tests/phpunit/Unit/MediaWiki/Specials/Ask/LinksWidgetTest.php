<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\LinksWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\LinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LinksWidgetTest extends \PHPUnit_Framework_TestCase {


	public function testEmbeddedCodeLink() {

		$instance = new LinksWidget();

		$this->assertInternalType(
			'string',
			LinksWidget::embeddedCodeLink()
		);
	}

	public function testEmbeddedCodeBlock() {

		$instance = new LinksWidget();

		$this->assertInternalType(
			'string',
			LinksWidget::embeddedCodeBlock( 'Foo' )
		);
	}

	public function testResultSubmitLinkHide() {

		$instance = new LinksWidget();

		$this->assertInternalType(
			'string',
			LinksWidget::resultSubmitLink( true )
		);
	}

	public function testResultSubmitLinkShow() {

		$instance = new LinksWidget();

		$this->assertInternalType(
			'string',
			LinksWidget::resultSubmitLink( false )
		);
	}

	public function testShowHideLink() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			LinksWidget::showHideLink( $title )
		);
	}

	public function testDebugLink() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			LinksWidget::debugLink( $title )
		);
	}

	public function testClipboardLink() {

		$infolink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			LinksWidget::clipboardLink( $infolink )
		);
	}

}
