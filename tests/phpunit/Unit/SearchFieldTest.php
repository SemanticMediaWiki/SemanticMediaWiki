<?php

namespace SMW\Tests;

use SMW\SearchField;

/**
 * @covers \SMW\SearchField
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SearchFieldTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider stringProvider
	 */
	public function testSearchField( $string, $expected ) {

		$this->assertEquals(
			$expected,
			SearchField::getSearchStringFrom( $string )
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testIndexField( $string, $expected ) {

		$this->assertEquals(
			$expected,
			SearchField::getIndexStringFrom( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			"ABC",
			"abc"
		);

		$provider[] = array(
			"lOrEm",
			"lorem"
		);

		$provider[] = array(
			"http://example.org",
			"example.org"
		);

		return $provider;
	}

}
