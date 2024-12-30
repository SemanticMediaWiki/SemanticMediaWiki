<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\LinksWidget;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\LinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LinksWidgetTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testFieldset() {
		$this->assertIsString(

			LinksWidget::fieldset()
		);
	}

	public function testEmbeddedCodeLink() {
		$this->assertIsString(

			LinksWidget::embeddedCodeLink()
		);
	}

	public function testEmbeddedCodeBlock() {
		$this->assertIsString(

			LinksWidget::embeddedCodeBlock( 'Foo' )
		);
	}

	public function testResultSubmitLinkHide() {
		$this->assertIsString(

			LinksWidget::resultSubmitLink( true )
		);
	}

	public function testResultSubmitLinkShow() {
		$this->assertIsString(

			LinksWidget::resultSubmitLink( false )
		);
	}

	public function testShowHideLink() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			LinksWidget::showHideLink( $title, $urlArgs )
		);
	}

	public function testDebugLink() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			LinksWidget::debugLink( $title, $urlArgs )
		);
	}

	public function testNoQCacheLink() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			LinksWidget::noQCacheLink( $title, $urlArgs, true )
		);
	}

	public function testNoQCacheLinkOnFalseFromCache() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEmpty(
			LinksWidget::noQCacheLink( $title, $urlArgs, false )
		);
	}

	public function testClipboardLink() {
		$infolink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			LinksWidget::clipboardLink( $infolink )
		);
	}

}
