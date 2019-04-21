<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Pipetrick;

/**
 * @covers \SMW\MediaWiki\Pipetrick
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author Morgon Kanter
 */
class PipetrickTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textProvider
	 */
	public function testPipetrick( $text, $expected ) {
		$this->assertEquals(
			$expected,
			Pipetrick::apply( $text )
		);
	}

	public function textProvider() {
		// https://en.wikipedia.org/wiki/Help:Pipe_trick

		yield [
			'Yours, Mine and Ours (1968 film)',
			'Yours, Mine and Ours'
		];

		yield [
			'Il Buono, il Brutto, il Cattivo',
			'Il Buono'
		];

		yield [
			':es:Wikipedia:Políticas',
			'Wikipedia:Políticas'
		];

		yield [
			'Wikipedia:Manual of Style (Persian)',
			'Manual of Style'
		];
	}

}
