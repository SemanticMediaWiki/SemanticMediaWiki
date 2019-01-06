<?php

namespace SMW\Tests\Utils;

use SMW\Utils\FileFetcher;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\FileFetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class FileFetcherTest extends \PHPUnit_Framework_TestCase {

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

		$this->setExpectedException( '\RuntimeException' );
		$instance->findByExtension( 'foo' );
	}

}
