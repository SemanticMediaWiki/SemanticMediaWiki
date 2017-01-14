<?php

namespace SMW\Tests\Utils;

use SMW\Utils\StatsFormatter;

/**
 * @covers \SMW\Utils\StatsFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StatsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider statsProvider
	 */
	public function testGetStatsFromFlatKey( $stats, $expected ) {

		$this->assertEquals(
			$expected,
			StatsFormatter::getStatsFromFlatKey( $stats )
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $stats, $format, $expected ) {

		$this->assertInternalType(
			$expected,
			StatsFormatter::format( $stats, $format )
		);
	}

	public function formatProvider() {

		$provider[] = array(
			array( 'Foo' => 1, 'Bar' => 1 ),
			StatsFormatter::FORMAT_PLAIN,
			'string'
		);

		$provider[] = array(
			array( 'Foo' => 1, 'Bar' => 1 ),
			StatsFormatter::FORMAT_HTML,
			'string'
		);

		$provider[] = array(
			array( 'Foo' => 1, 'Bar' => 1 ),
			StatsFormatter::FORMAT_JSON,
			'string'
		);

		$provider[] = array(
			array( 'Foo' => 1, 'Bar' => 1 ),
			null,
			'array'
		);

		return $provider;
	}

	public function statsProvider() {

		$provider[] = array(
			array( 'Foo' => 1, 'Bar' => 1 ),
			array(
				'Foo' => 1,
				'Bar' => 1
			)
		);

		$provider[] = array(
			array( 'Foo.foobar' => 1, 'Bar' => 1 ),
			array(
				'Foo' => array( 'foobar' => 1 ),
				'Bar' => 1
			)
		);

		$provider[] = array(
			array( 'Foo.foobar' => 5, 'Bar' => 1, 'Foo.foobar.baz' => 1 ),
			array(
				'Foo' => array( 'foobar' => array( 5, 'baz' => 1 ) ),
				'Bar' => 1
			)
		);

		return $provider;
	}

}
