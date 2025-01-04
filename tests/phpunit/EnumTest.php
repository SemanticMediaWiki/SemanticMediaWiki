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
class EnumTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 *@dataProvider constProvider
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
