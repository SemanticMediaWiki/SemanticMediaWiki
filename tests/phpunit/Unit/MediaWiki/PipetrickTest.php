<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Pipetrick;

/**
 * @covers \SMW\MediaWiki\Pipetrick
 * @group semantic-mediawiki
 */
class PipetrickTest extends \PHPUnit_Framework_TestCase {
	public function testPipetrick() {
		$in = 'Yours, Mine and Ours (1968 film)';
		$this->assertEquals('Yours, Mine and Ours', Pipetrick::apply($in));

		$in = 'Il Buono, il Brutto, il Cattivo';
		$this->assertEquals('Il Buono', Pipetrick::apply($in));
	}
}
