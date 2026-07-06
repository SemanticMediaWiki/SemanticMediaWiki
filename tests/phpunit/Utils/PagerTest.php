<?php

namespace SMW\Tests\Utils;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Utils\Pager;

/**
 * @covers \SMW\Utils\Pager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PagerTest extends TestCase {

	public function testFilter() {
		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( 'Test' );

		$this->assertIsString(

			Pager::filter( $title )
		);
	}

	public function testFilterPreservesTitleWithSlash() {
		$title = $this->createMock( Title::class );
		$title->method( 'getPrefixedText' )
			->willReturn( 'Property:Task/Desc' );

		$html = Pager::filter( $title );

		// The hidden 'title' field must carry the full prefixed title so the
		// filter form reloads the correct page, not a title truncated at the
		// first slash (#6142).
		$this->assertStringContainsString( 'value="Property:Task/Desc"', $html );
		$this->assertStringNotContainsString( 'value="Property:Task"', $html );
	}

}
