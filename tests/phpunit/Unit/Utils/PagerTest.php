<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Pager;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\Pager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PagerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testFilter() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			Pager::filter( $title )
		);
	}

}
