<?php

namespace SMW\Tests;

use SMW\Encoder;

/**
 * @covers \SMW\Encoder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EncoderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Encoder',
			new Encoder()
		);
	}

	public function testEscape() {

		$this->assertEquals(
			'-3C-5B-23-26-25!~`+=-7C-2D-5F-5D-3E',
			Encoder::escape( '<[#&%!~`+=|-_]>' )
		);
	}

	public function testUnescape() {

		$this->assertEquals(
			'<[#&%!~`+=|-_]>',
			Encoder::unescape( '-3C-5B-23-26-25!~`+=-7C-2D-5F-5D-3E' )
		);
	}

	public function testEncode() {

		$this->assertEquals(
			'%3C%5B%23%26%25%21~%60%2B%3D%7C-_%5D%3E',
			Encoder::encode( '<[#&%!~`+=|-_]>' )
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testDecode( $input, $output ) {

		$this->assertEquals(
			$output,
			Encoder::decode( $input )
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function testEncodeDecode( $input, $output ) { // @codingStandardsIgnoreEnd

		if ( $output === ' ' ) {
			$output = '';
		}

		$this->assertEquals(
			$output,
			Encoder::decode( Encoder::encode( Encoder::escape( $output ) ) )
		);
	}

	public function stringProvider() {

		$provider = [];

		$provider[] = [ ' ', '' ];
		$provider[] = [ ' &nbsp;', ' ' ];

		$provider[] = [ '2013/11/05', '2013/11/05' ];
		$provider[] = [ '2013-2F11-2F05', '2013/11/05' ];

		$provider[] = [ '2013$06&30', '2013$06&30' ];
		$provider[] = [ '2013-2D06-2D30', '2013-06-30' ];

		$provider[] = [ '2013-2406-2630', '2013$06&30' ];
		$provider[] = [ '2013%2B06%2B30', '2013+06+30' ];
		// $provider[] = array( '2013-06-30', '2013-06-30' );

		$provider[] = [ '「」東京', '「」東京' ];
		$provider[] = [ 'http:-2F-2F127.0.0.1-2F%E3%80%8C%E3%80%8D%E6%9D%B1%E4%BA%AC/2F-2F', 'http://127.0.0.1/「」東京/2F/' ];

		$provider[] = [ 'Foo(-3-)', 'Foo(-3-)' ];
		$provider[] = [ '"Fo"o', '"Fo"o' ];

		$provider[] = [ 'Foo_bar', 'Foo_bar' ];
		$provider[] = [ 'Has-20url', 'Has url' ];

		$provider[] = [ 'F &oo=?', 'F &oo=?' ];
		$provider[] = [ 'F+%26oo%3D%3F', 'F+&oo=?' ];
		$provider[] = [ 'F_%26oo%3D%3F', 'F_&oo=?' ];

		$provider[] = [ 'search&foo=Bar&', 'search&foo=Bar&' ];
		$provider[] = [ 'âêîôûëïçé', 'âêîôûëïçé' ];

		$provider[] = [ 'Has+Foo%28-3-%29%26', 'Has+Foo(-3-)&' ];
		$provider[] = [ 'Has_Foo(-3-)%26', 'Has_Foo(-3-)&' ];

		return $provider;
	}

}
