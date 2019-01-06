<?php

namespace SMW\Tests\Integration\Utils;

use SMW\Utils\FileFetcher;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class FileFetcherRoundTripTest extends \PHPUnit_Framework_TestCase {

	public function testRoundTrip() {

		$found = false;

		$fileFetcher = new FileFetcher( __DIR__ );
		$iterator = $fileFetcher->findByExtension( 'php' );

		$this->assertInstanceof(
			'\Iterator',
			$iterator
		);

		foreach ( $iterator as $key => $value ) {
			if ( strpos( $key, 'FileFetcherRoundTripTest.php' ) !== false ) {
				$found = true;
			}
		}

		$this->assertTrue( $found );
	}

}
