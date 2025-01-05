<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\DownloadLinksWidget;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\DownloadLinksWidget
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DownloadLinksWidgetTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
			'<div id="ask-export-links" class="smw-ask-downloadlinks export-links">',
			DownloadLinksWidget::downloadLinks( $infolink )
		);
	}

}
