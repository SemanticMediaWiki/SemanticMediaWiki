<?php

namespace SMW\Tests\Libs;

use SMW\Libs\ErrorCode;

/**
 * @covers \SMW\Libs\ErrorCode
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorCodeTest extends \PHPUnit_Framework_TestCase {

	public function testGetStringFromJsonErrorCode() {

		$this->assertInternalType(
			'string',
			ErrorCode::getStringFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertInternalType(
			'string',
			ErrorCode::getStringFromJsonErrorCode( json_last_error() )
		);
	}

	public function testGetMessageFromJsonErrorCode() {

		$this->assertInternalType(
			'string',
			ErrorCode::getMessageFromJsonErrorCode( 'Foo' )
		);

		$contents = json_decode( '{ Foo: Bar }' );

		$this->assertInternalType(
			'string',
			ErrorCode::getMessageFromJsonErrorCode( json_last_error() )
		);
	}

}
