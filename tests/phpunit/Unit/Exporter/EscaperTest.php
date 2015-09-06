<?php

namespace SMW\Tests\Exporter;

use SMW\Exporter\Escaper;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Exporter\Escaper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EscaperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider encodePageProvider
	 */
	public function testEncodePage( $page, $expected ) {

		$this->assertSame(
			$expected,
			Escaper::encodePage( $page )
		);
	}


	/**
	 * @dataProvider encodeUriProvider
	 */
	public function testEncodeUri( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			Escaper::encodeUri( $uri )
		);

		$this->assertEquals(
			$uri,
			Escaper::decodeUri( Escaper::encodeUri( $uri ) )
		);
	}

	/**
	 * @dataProvider decodeUriProvider
	 */
	public function testDecodeUri( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			Escaper::decodeUri( $uri )
		);

		$this->assertEquals(
			$uri,
			Escaper::encodeUri( Escaper::decodeUri( $uri ) )
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

	public function encodePageProvider() {

		#0
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, '', '' )
			, 'Foo'
		);

		#1
		$provider[] = array(
			new DIWikiPage( 'Foo_bar', NS_MAIN, '', '' ),
			'Foo_bar'
		);

		#2
		$provider[] = array(
			new DIWikiPage( 'Foo%bar', NS_MAIN, '', '' ),
			'Foo-25bar'
		);

		#3 / #759
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', '' ),
			'bar-3AFoo'
		);

		#4 / #759
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', 'yuu' ),
			'bar-3AFoo'
		);

		return $provider;
	}

}
