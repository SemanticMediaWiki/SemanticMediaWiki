<?php

namespace SMW\Tests\Utils;

use MediaWiki\Title\Title;
use SMW\Tests\PHPUnitCompat;
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
class PagerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testFilter() {
		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( 'Test' );

		$this->assertIsString(

			Pager::filter( $title )
		);
	}

}
