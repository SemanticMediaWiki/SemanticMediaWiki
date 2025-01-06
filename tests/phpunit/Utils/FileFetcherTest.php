<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
use SMW\Utils\FileFetcher;

/**
 * @covers \SMW\Utils\FileFetcher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class FileFetcherTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$instance = new FileFetcher();

		$this->assertInstanceOf(
			FileFetcher::class,
			$instance
		);
	}

	public function testFindByExtensionThrowsException() {
		$instance = new FileFetcher();

		$this->expectException( '\RuntimeException' );
		$instance->findByExtension( 'foo' );
	}

}
