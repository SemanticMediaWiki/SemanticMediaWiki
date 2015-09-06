<?php

namespace SMW\Tests;

use SMW\UrlEncoder;

/**
 * @covers \SMW\UrlEncoder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UrlEncoderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\UrlEncoder',
			new UrlEncoder()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testDecode( $input, $output ) {

		$this->assertEquals(
			$output,
			UrlEncoder::decode( $input )
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
			UrlEncoder::decode( UrlEncoder::encode( UrlEncoder::replace( $output ) ) )
		);
	}

	public function stringProvider() {

		$provider = array();

		$provider[] = array( ' ', '' );
		$provider[] = array( ' &nbsp;', ' ' );

		$provider[] = array( '2013/11/05', '2013/11/05' );
		$provider[] = array( '2013-2F11-2F05', '2013/11/05' );

		$provider[] = array( '2013$06&30', '2013$06&30' );
		$provider[] = array( '2013-2D06-2D30', '2013-06-30' );

		$provider[] = array( '2013-2406-2630', '2013$06&30' );
		$provider[] = array( '2013%2B06%2B30', '2013+06+30' );
		// $provider[] = array( '2013-06-30', '2013-06-30' );

		$provider[] = array( '「」東京', '「」東京' );
		$provider[] = array( 'http:-2F-2F127.0.0.1-2F%E3%80%8C%E3%80%8D%E6%9D%B1%E4%BA%AC/2F-2F', 'http://127.0.0.1/「」東京/2F/' );

		$provider[] = array( 'Foo(-3-)', 'Foo(-3-)' );
		$provider[] = array( '"Fo"o', '"Fo"o' );

		$provider[] = array( 'Foo_bar', 'Foo_bar' );
		$provider[] = array( 'Has-20url', 'Has url' );

		$provider[] = array( 'F &oo=?', 'F &oo=?' );
		$provider[] = array( 'F+%26oo%3D%3F', 'F+&oo=?' );
		$provider[] = array( 'F_%26oo%3D%3F', 'F_&oo=?' );

		$provider[] = array( 'search&foo=Bar&', 'search&foo=Bar&' );
		$provider[] = array( 'âêîôûëïçé', 'âêîôûëïçé' );

		$provider[] = array( 'Has+Foo%28-3-%29%26', 'Has+Foo(-3-)&' );
		$provider[] = array( 'Has_Foo(-3-)%26', 'Has_Foo(-3-)&' );

		return $provider;
	}

}
