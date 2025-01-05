<?php

namespace SMW\Tests;

use SMW\Enum;

/**
 * @covers \SMW\Enum
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class EnumTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider constProvider
	 */
	public function testValidate( $const ) {
		$this->assertIsString(

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
