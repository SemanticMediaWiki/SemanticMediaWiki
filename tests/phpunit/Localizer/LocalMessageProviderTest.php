<?php

namespace SMW\Tests\Localizer;

use SMW\Localizer\LocalMessageProvider;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Localizer\LocalMessageProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class LocalMessageProviderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			LocalMessageProvider::class,
			new LocalMessageProvider( 'Foo', 'en' )
		);
	}

	public function testMsg() {
		$instance = new LocalMessageProvider( 'test.json', 'en' );
		$instance->setLanguageFileDir( \SMW_PHPUNIT_DIR . '/Fixtures/Localizer' );
		$instance->setLanguageCode( 'ja' );
		$instance->loadMessages();

		$this->assertEquals(
			'あいう',
			$instance->msg( 'foo-abc' )
		);
	}

	public function testMsg_Fallback() {
		$instance = new LocalMessageProvider( 'test.json', 'en' );
		$instance->setLanguageFileDir( \SMW_PHPUNIT_DIR . '/Fixtures/Localizer' );
		$instance->setLanguageCode( 'foo' );
		$instance->loadMessages();

		$this->assertEquals(
			'abc',
			$instance->msg( 'foo-abc' )
		);
	}

	public function testMsg_WithArgs() {
		$instance = new LocalMessageProvider( 'test.json', 'en' );
		$instance->setLanguageFileDir( \SMW_PHPUNIT_DIR . '/Fixtures/Localizer' );
		$instance->setLanguageCode( 'ja' );
		$instance->loadMessages();

		$this->assertEquals(
			'Foo あいう Bar',
			$instance->msg( [ 'foo-abc-replacement', 'Foo', 'Bar' ] )
		);
	}

	public function testMsg_NoValidKey() {
		$instance = new LocalMessageProvider( 'test.json', 'en' );
		$instance->setLanguageFileDir( \SMW_PHPUNIT_DIR . '/Fixtures/Localizer' );
		$instance->loadMessages();

		$this->assertEquals(
			'⧼foo-bar⧽',
			$instance->msg( 'foo-bar' )
		);
	}

}
