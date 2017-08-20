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

	public function testFieldset() {

		$this->assertInternalType(
			'string',
			LinksWidget::fieldset()
		);
	}

	public function testEmbeddedCodeLink() {

		$this->assertInternalType(
			'string',
			LinksWidget::embeddedCodeLink()
		);
	}

	public function testEmbeddedCodeBlock() {

		$this->assertInternalType(
			'string',
			LinksWidget::embeddedCodeBlock( 'Foo' )
		);
	}

	public function testResultSubmitLinkHide() {

		$this->assertInternalType(
			'string',
			LinksWidget::resultSubmitLink( true )
		);
	}

	public function testResultSubmitLinkShow() {

		$this->assertInternalType(
			'string',
			LinksWidget::resultSubmitLink( false )
		);
	}

	public function testShowHideLink() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			LinksWidget::showHideLink( $title, $urlArgs )
		);
	}

	public function testDebugLink() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			LinksWidget::debugLink( $title, $urlArgs )
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
