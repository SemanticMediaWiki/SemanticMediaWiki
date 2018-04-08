<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Normalizer;

/**
 * @covers \SMW\Utils\Normalizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NormalizerTest extends \PHPUnit_Framework_TestCase {

	public function testReduceLengthTo() {

		$this->assertEquals(
			'ABC',
			Normalizer::reduceLengthTo( 'ABCDEF', 3 )
		);

		$this->assertEquals(
			'ABCDEF',
			Normalizer::reduceLengthTo( 'ABCDEF' )
		);

		$this->assertEquals(
			'ABCD',
			Normalizer::reduceLengthTo( 'ABCD EF', 4 )
		);

		$this->assertEquals(
			'ABC',
			Normalizer::reduceLengthTo( 'ABC D EF', 4 )
		);

		$this->assertEquals(
			'ABCD',
			Normalizer::reduceLengthTo( 'ABCD EF', 5 )
		);

		$this->assertEquals(
			'abc def gh',
			Normalizer::reduceLengthTo( 'abc def gh in 123', 12 )
		);
	}

	public function testToLowercase() {

		$this->assertEquals(
			'abcdef',
			Normalizer::toLowercase( 'ABCDEF' )
		);
	}

}
