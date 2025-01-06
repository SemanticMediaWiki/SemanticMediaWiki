<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
use SMW\Utils\ErrorCodeFormatter;

/**
 * @covers \SMW\Utils\ErrorCodeFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorCodeFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGetStringFromJsonErrorCode() {
		$this->assertIsString(

			ErrorCodeFormatter::getStringFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertIsString(

			ErrorCodeFormatter::getStringFromJsonErrorCode( json_last_error() )
		);
	}

	public function testGetMessageFromJsonErrorCode() {
		$this->assertIsString(

			ErrorCodeFormatter::getMessageFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertIsString(

			ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() )
		);
	}

}
