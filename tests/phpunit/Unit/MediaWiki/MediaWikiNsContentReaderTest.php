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
class MediaWikiNsContentReaderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MediaWikiNsContentReader',
			new MediaWikiNsContentReader()
		);
	}

	public function testReadFromMessageCache() {

		$instance = new MediaWikiNsContentReader();

		$this->assertInternalType(
			'string',
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

		$this->assertInternalType(
			'string',
			$instance->read( __METHOD__ )
		);
	}

}
