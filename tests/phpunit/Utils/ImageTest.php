<?php

namespace SMW\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Utils\Image;

/**
 * @covers \SMW\Utils\Image
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ImageTest extends TestCase {

	public function testIsImage() {
		$this->assertTrue(
			Image::isImage( WikiPage::newFromText( 'Foo.png', NS_FILE ) )
		);

		$this->assertTrue(
			Image::isImage( WikiPage::newFromText( '一二三.png', NS_FILE ) )
		);

		$this->assertFalse(
			Image::isImage( WikiPage::newFromText( 'Foo.png', NS_MAIN ) )
		);

		$this->assertFalse(
			Image::isImage( WikiPage::newFromText( 'Foo', NS_FILE ) )
		);
	}

}
