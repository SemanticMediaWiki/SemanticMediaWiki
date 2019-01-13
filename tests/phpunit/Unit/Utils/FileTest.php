<?php

namespace SMW\Tests\Utils;

use SMW\Utils\File;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	public function testCanConstruct() {

		$instance = new File();

		$this->assertInstanceOf(
			File::class,
			$instance
		);
	}

	public function testWrite_ThrowsException() {

		$instance = new File();

		$this->setExpectedException( '\SMW\Exception\FileNotWritableException' );
		$instance->write( 'abc/Foo', '' );
	}

	public function testDir() {

		$this->assertInternalType(
			'string',
			File::dir( 'foo' )
		);
	}

}
