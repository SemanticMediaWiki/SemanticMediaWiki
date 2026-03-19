<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Ask\DownloadLinksWidget;
use SMWInfolink;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\DownloadLinksWidget
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DownloadLinksWidgetTest extends TestCase {

	public function testOnNull() {
		$this->assertEmpty(
			DownloadLinksWidget::downloadLinks( null )
		);
	}

	public function testLinks() {
		$infolink = $this->getMockBuilder( SMWInfolink::class )
			->disableOriginalConstructor()
			->getMock();

		$infolink->expects( $this->atLeastOnce() )
			->method( 'setParameter' );

		$this->assertStringContainsString(
			'<div id="ask-export-links" class="smw-ask-downloadlinks export-links">',
			DownloadLinksWidget::downloadLinks( $infolink )
		);
	}

}
