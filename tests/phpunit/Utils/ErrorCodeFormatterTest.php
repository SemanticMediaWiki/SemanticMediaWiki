<?php

namespace SMW\Tests\Utils;

use SMW\Utils\ErrorCodeFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\ErrorCodeFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
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
