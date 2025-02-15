<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
use SMW\Utils\TempFile;

/**
 * @covers \SMW\Utils\TempFile
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TempFileTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGenerate() {
		$instance = new TempFile();

		$this->assertContains(
			'6na5ojj24og0',
			$instance->generate( 'Foo' )
		);

		$this->assertContains(
			'Bar7vmi67tsvkb0',
			$instance->generate( 'Bar', 'Foo' )
		);
	}

}
