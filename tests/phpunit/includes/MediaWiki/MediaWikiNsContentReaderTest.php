<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @covers \SMW\MediaWiki\MediaWikiNsContentReader
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MediaWikiNsContentReaderTest extends \PHPUnit_Framework_TestCase {

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

	public function testNotUseDatabaseForFallback() {

		$instance = new MediaWikiNsContentReader();
		$instance->useDatabaseForFallback( false );

		$this->assertInternalType(
			'string',
			$instance->read( __METHOD__ )
		);
	}

	public function testUseDatabaseForFallback() {

		$instance = new MediaWikiNsContentReader();
		$instance->useDatabaseForFallback( true );

		$this->assertInternalType(
			'string',
			$instance->read( __METHOD__ )
		);
	}

}
