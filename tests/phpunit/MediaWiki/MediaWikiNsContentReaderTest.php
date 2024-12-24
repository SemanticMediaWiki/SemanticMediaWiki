<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\MediaWikiNsContentReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MediaWikiNsContentReaderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\MediaWikiNsContentReader',
			new MediaWikiNsContentReader()
		);
	}

	public function testReadFromMessageCache() {
		$instance = new MediaWikiNsContentReader();

		$this->assertIsString(

			$instance->read( 'smw-desc' )
		);
	}

	public function testTryToReadForInvalidTitle() {
		$instance = new MediaWikiNsContentReader();

		$this->assertEmpty(
			$instance->read( '{}' )
		);
	}

	public function testSkipMessageCache() {
		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MediaWikiNsContentReader();
		$instance->skipMessageCache();

		$instance->setRevisionGuard( $revisionGuard );

		$this->assertIsString(

			$instance->read( __METHOD__ )
		);
	}

}
