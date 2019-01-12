<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Csv;

/**
 * @covers \SMW\Utils\Csv
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CsvTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider rowsProvider
	 */
	public function testConcatenate( $rows, $sep, $expected ) {

		$instance = new Csv();

		$this->assertEquals(
			$expected,
			$instance->merge( $rows, $sep )
		);
	}

	/**
	 * @dataProvider makeProvider
	 */
	public function testMake( $header, $rows, $sep, $show, $expected ) {

		$instance = new Csv( $show );

		$this->assertEquals(
			$expected,
			$instance->toString( $header, $rows, $sep )
		);
	}

	public function testWithBOM() {

		$header = [];
		$rows = [
			[ 'Foo', '1', '2', '3' ]
		];

		$bom = ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		$instance = new Csv( false, true );

		$this->assertEquals(
			"{$bom}Foo,1,2,3\n",
			$instance->toString( $header, $rows, ',' )
		);
	}

	public function rowsProvider() {

		// No change
		yield [
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',',
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			]
		];

		// Concatenate duplicate
		yield [
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Foo', '1', '2', '3' ],
			],
			',',
			[
				[ 'Foo', '1', '2', '3' ]
			]
		];

		// Concatenate column values
		yield [
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Foo', '1', '2', '4' ],
			],
			',',
			[
				[ 'Foo', '1', '2', '3,4' ]
			]
		];

		// Concatenate column values
		yield [
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Foo', '1', '2', '3', '4' ],
			],
			',',
			[
				[ 'Foo', '1', '2', '3' ]
			]
		];

		yield [
			[
				[ 'Foo', '1', '2', '3', '4' ],
				[ 'Foo', '1', '2', '3' ],
			],
			',',
			[
				[ 'Foo', '1', '2', '3', '4' ]
			]
		];

		yield [
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
				[ 'Foo', '4', '5', '6' ],
				[ 'Bar', 'A', 'B', 'C' ],
			],
			',',
			[
				[ 'Foo', '1,4', '2,5', '3,6' ],
				[ 'Bar', '1,A', '2,B', '3,C' ]
			]
		];

		yield [
			[
				[ 'Foo', '1', '2', '3;6;2' ],
				[ 'Bar', '1', '2', '3' ],
				[ 'Foo', '4', '5', '6' ],
				[ 'Bar', 'A', 'B', 'C' ],
			],
			';',
			[
				[ 'Foo', '1;4', '2;5', '3;6;2' ],
				[ 'Bar', '1;A', '2;B', '3;C' ]
			]
		];
	}

	public function makeProvider() {

		// Without header
		yield [
			[],
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',',
			false,
			"Foo,1,2,3\nBar,1,2,3\n"
		];

		// Without header, multiple value assignment
		yield [
			[],
			[
				[ 'Foo', '1', '2', '3,4' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',',
			false,
			"Foo,1,2,\"3,4\"\nBar,1,2,3\n"
		];

		// With header
		yield [
			[ 'H1', 'H2', 'H3', 'H4' ],
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',',
			false,
			"H1,H2,H3,H4\nFoo,1,2,3\nBar,1,2,3\n"
		];

		// With header
		yield [
			[ 'H1', 'H2', 'H3', 'H4' ],
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',',
			true,
			"sep=,\nH1,H2,H3,H4\nFoo,1,2,3\nBar,1,2,3\n"
		];

		// fputcsv ... delimiter must be a single character
		yield [
			[],
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			',..;',
			false,
			"Foo,1,2,3\nBar,1,2,3\n"
		];

		// fputcsv ... delimiter must be a single character
		yield [
			[],
			[
				[ 'Foo', '1', '2', '3' ],
				[ 'Bar', '1', '2', '3' ],
			],
			'',
			false,
			"Foo,1,2,3\nBar,1,2,3\n"
		];
	}

}
