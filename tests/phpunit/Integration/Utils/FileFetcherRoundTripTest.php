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

	public function testRoundTrip_Sort_Asc() {

		$fileFetcher = new FileFetcher(
			FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/' )
		);

		$fileFetcher->sort( 'asc' );
		$fileFetcher->setMaxDepth( 0 );

		$files = $fileFetcher->findByExtension( 'json' );

		$this->assertEquals(
			[
				[ FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/aaa.json' ) ],
				[ FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/zzz.json' ) ]
			],
			$files
		);
	}

	public function testRoundTrip_Sort_Desc() {

		$fileFetcher = new FileFetcher(
			FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/' )
		);

		$fileFetcher->sort( 'desc' );
		$fileFetcher->setMaxDepth( 0 );

		$files = $fileFetcher->findByExtension( 'json' );

		$this->assertEquals(
			[
				[ FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/zzz.json' ) ],
				[ FileFetcher::normalize( __DIR__ . '/../../Fixtures/Utils/aaa.json' ) ]
			],
			$files
		);
	}

	public function testRoundTrip_Sort_Desc_SubDir() {

		$fileFetcher = new FileFetcher(
			FileFetcher::normalize( SMW_PHPUNIT_DIR . '/Fixtures/Utils/' )
		);

		$fileFetcher->sort( 'desc' );
		$fileFetcher->setMaxDepth( 1 );

		$files = $fileFetcher->findByExtension( 'json' );

		$this->assertEquals(
			[
				[ FileFetcher::normalize( SMW_PHPUNIT_DIR . '/Fixtures/Utils/zzz.json' ) ],
				[ FileFetcher::normalize( SMW_PHPUNIT_DIR . '/Fixtures/Utils/subDir/bbb.json' ) ],
				[ FileFetcher::normalize( SMW_PHPUNIT_DIR . '/Fixtures/Utils/aaa.json' ) ]
			],
			$files
		);
	}

}
