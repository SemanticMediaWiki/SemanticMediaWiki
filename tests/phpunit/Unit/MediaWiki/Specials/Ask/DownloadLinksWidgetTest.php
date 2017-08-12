<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\DownloadLinksWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\DownloadLinksWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DownloadLinksWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testOnNull() {

		$this->assertEmpty(
			DownloadLinksWidget::downloadLinks( null )
		);
	}

	public function testLinks() {

		$infolink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$infolink->expects( $this->atLeastOnce() )
			->method( 'setParameter' );

		$this->assertContains(
			'<span class="smw-ask-downloadlinks">',
			DownloadLinksWidget::downloadLinks( $infolink )
		);
	}

}
