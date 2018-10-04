<?php

namespace SMW\Tests\Utils;

use SMW\Utils\ErrorCodeFormatter;

/**
 * @covers \SMW\Utils\ErrorCodeFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorCodeFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testGetStringFromJsonErrorCode() {

		$this->assertInternalType(
			'string',
			ErrorCodeFormatter::getStringFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertInternalType(
			'string',
			ErrorCodeFormatter::getStringFromJsonErrorCode( json_last_error() )
		);
	}

	public function testGetMessageFromJsonErrorCode() {

		$this->assertInternalType(
			'string',
			ErrorCodeFormatter::getMessageFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertInternalType(
			'string',
			ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() )
		);
	}

}
