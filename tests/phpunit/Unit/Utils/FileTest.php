<?php

namespace SMW\Tests\Utils;

use SMW\Utils\File;

/**
 * @covers \SMW\Utils\File
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new File();

		$this->assertInstanceOf(
			File::class,
			$instance
		);
	}

}
