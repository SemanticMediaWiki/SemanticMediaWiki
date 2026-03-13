<?php

namespace SMW\Tests\Utils;

use SMW\Utils\File;

/**
 * @covers \SMW\Utils\File
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FileTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new File();

		$this->assertInstanceOf(
			File::class,
			$instance
		);
	}

	public function testWrite_ThrowsException() {
		$instance = new File();

		$this->expectException( '\SMW\Exception\FileNotWritableException' );
		$instance->write( 'abc/Foo', '' );
	}

	public function testDir() {
		$this->assertIsString(

			File::dir( 'foo' )
		);
	}

}
