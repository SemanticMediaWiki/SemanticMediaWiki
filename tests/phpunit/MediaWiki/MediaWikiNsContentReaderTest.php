<?php

namespace SMW\Tests\MediaWiki;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\RevisionGuard;

/**
 * @covers \SMW\MediaWiki\MediaWikiNsContentReader
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class MediaWikiNsContentReaderTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MediaWikiNsContentReader::class,
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
		$revisionGuard = $this->getMockBuilder( RevisionGuard::class )
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
