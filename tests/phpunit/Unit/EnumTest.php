<?php

namespace SMW\Tests;

use SMW\Enum;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Enum
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class EnumTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	/**
	 *@dataProvider constProvider
	 */
	public function testValidate( $const ) {

		$this->assertInternalType(
			'string',
			$const
		);
	}

	public function constProvider() {

		$provider[] = [
			Enum::OPT_SUSPEND_PURGE
		];

		return $provider;
	}

}
