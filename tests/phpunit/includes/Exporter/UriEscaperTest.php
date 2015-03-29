<?php

namespace SMW\Tests\Exporter;

use SMW\Exporter\UriEscaper;

/**
 * @covers \SMW\Exporter\UriEscaper
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class UriEscaperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider encodeUriProvider
	 */
	public function testEncode( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			UriEscaper::encode( $uri )
		);

		$this->assertEquals(
			$uri,
			UriEscaper::decode( UriEscaper::encode( $uri ) )
		);
	}

	/**
	 * @dataProvider decodeUriProvider
	 */
	public function testDecode( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			UriEscaper::decode( $uri )
		);

		$this->assertEquals(
			$uri,
			UriEscaper::encode( UriEscaper::decode( $uri ) )
		);
	}

	public function encodeUriProvider() {

		$provider[] = array(
			'Foo:"&+!%#',
			'Foo-3A-22-26-2B-21--23'
		);

		$provider[] = array(
			"Foo'-'",
			'Foo-27-2D-27'
		);
		return $provider;
	}

	public function decodeUriProvider() {

		$provider[] = array(
			'Foo-3A-22-26-2B-21--23',
			'Foo:"&+!%#'
		);

		$provider[] = array(
			'Foo-27-2D-27',
			"Foo'-'"
		);

		return $provider;
	}

}
