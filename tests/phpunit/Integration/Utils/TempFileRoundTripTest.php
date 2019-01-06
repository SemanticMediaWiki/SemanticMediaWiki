<?php

namespace SMW\Tests\Integration\Utils;

use SMW\Utils\TempFile;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TempFileRoundTripTest extends \PHPUnit_Framework_TestCase {

	public function testRoundTrip() {

		$expected = 'Test write file';
		$tempFile = new TempFile();

		$file = $tempFile->generate( 'Test' );

		$tempFile->write( $file, $expected );

		$this->assertTrue(
			$tempFile->exists( $file )
		);

		$this->assertEquals(
			$expected,
			$tempFile->read( $file, $tempFile->getCheckSum( $file ) )
		);

		$tempFile->write( $file, '++plus++', FILE_APPEND );

		$this->assertEquals(
			$expected . '++plus++',
			$tempFile->read( $file )
		);

		$tempFile->delete( $file );

		$this->assertFalse(
			$tempFile->exists( $file )
		);
	}

}
