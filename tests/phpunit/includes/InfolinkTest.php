<?php

namespace SMW\Test;

use SMWInfolink;

/**
 * Tests for the SMWInfolink class
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMWInfolink class
 * @covers \SMWInfolink
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class InfolinkTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWInfolink';
	}

	/**
	 * Parameter dataProvider
	 *
	 * @return array
	 */
	public function getParameterDataProvider() {
		return array(
			array(
				// #0
				array(
					'format=template',
					'link=none'
				),
				array(
					'format=template/link=none',
					'x=format%3Dtemplate%2Flink%3Dnone'
				)
			),

			// #1 Bug 47010 (space encoding, named args => named%20args)
			array(
				array(
					'format=template',
					'link=none',
					'named args=1'
				),
				array(
					'format=template/link=none/named-20args=1',
					'x=format%3Dtemplate%2Flink%3Dnone%2Fnamed-20args%3D1'
				)
			),

			// #2 "\"
			array(
				array(
					"format=foo\bar",
				),
				array(
					'format=foo-5Cbar',
					'x=format%3Dfoo-5Cbar'
				)
			),
		);
	}

	/**
	 * @test SMWInfolink::encodeParameters
	 * @dataProvider getParameterDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expectedEncode
	 */
	public function testEncodeParameters( array $params, array $expectedEncode ) {
		$encodeResult = SMWInfolink::encodeParameters( $params, true );
		$this->assertEquals( $expectedEncode[0], $encodeResult );

		$encodeResult = SMWInfolink::encodeParameters( $params, false );
		$this->assertEquals( $expectedEncode[1], $encodeResult );
	}
}
