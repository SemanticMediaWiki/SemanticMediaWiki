<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Pager;
use SMW\Tests\PHPUnitCompat;
use Title;

/**
 * @covers \SMW\Utils\Pager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
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
