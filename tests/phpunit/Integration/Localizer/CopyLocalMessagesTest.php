<?php

namespace SMW\Tests\Integration\Localizer;

use SMW\Localizer\CopyLocalMessages;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Localizer\CopyLocalMessages
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class CopyLocalMessagesTest extends \PHPUnit_Framework_TestCase {

	private $canonicalMessages;
	private $translatedMessages;

	protected function setUp() : void {
		$this->canonicalMessages = file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/en.json' );
		$this->translatedMessages = file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/test.json' );
	}

	protected function tearDown() : void {
		file_put_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/en.json', $this->canonicalMessages );
		file_put_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/test.json', $this->translatedMessages );
	}

	public function testCopyCanonicalMessages() {

		$instance = new CopyLocalMessages(
			'test.json',
			SMW_PHPUNIT_DIR . '/Fixtures/Localizer'
		);

		$this->assertEquals(
			[ 'messages_count' => 2 ],
			$instance->copyCanonicalMessages()
		);

		$canonicalMessages = json_decode(
			file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/en.json' ),
			true
		);

		$this->assertArrayHasKey(
			'foo-abc',
			$canonicalMessages
		);


		$this->assertArrayHasKey(
			'foo-abc-replacement',
			$canonicalMessages
		);
	}

	public function tesCopyTranslatedMessages() {

		$instance = new CopyLocalMessages(
			'test.json',
			SMW_PHPUNIT_DIR . '/Fixtures/Localizer'
		);

		$this->assertEquals(
			[
				'files_count' => 1,
				'messages_count' => 2
			],
			$instance->copyTranslatedMessages()
		);

		$translatedMessages = json_decode(
			file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Localizer/test.json' ),
			true
		);

		$this->assertEquals(
			'_dk-foo-abc',
			$translatedMessages['foo-abc']['dk']
		);


		$this->assertEquals(
			'_dk-foo-abc-replacement',
			$translatedMessages['foo-abc-replacement']['dk']
		);
	}

}
